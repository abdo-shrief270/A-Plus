<?php

namespace App\Http\Controllers\Api\v2\Auth;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\v2\Auth\ChangePasswordRequest;
use App\Http\Requests\Api\v2\Auth\CheckUsernameRequest;
use App\Http\Requests\Api\v2\Auth\LoginCheckRequest;
use App\Http\Requests\Api\v2\Auth\LoginRequest;
use App\Http\Requests\Api\v2\Auth\RegisterParentRequest;
use App\Http\Requests\Api\v2\Auth\RegisterStudentRequest;
use App\Http\Requests\Api\v2\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\v2\Auth\SendOtpRequest;
use App\Http\Requests\Api\v2\Auth\UpdateProfileRequest;
use App\Http\Requests\Api\v2\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\Auth\DeviceService;
use App\Services\Auth\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends BaseApiController
{
    public function __construct(
        protected AuthService $authService,
        protected DeviceService $deviceService,
        protected OtpService $otpService
    ) {
    }

    /**
     * Check if username is available.
     */
    public function checkUsername(CheckUsernameRequest $request): JsonResponse
    {
        $exists = User::where('user_name', $request->user_name)->exists();

        return $this->successResponse([
            'available' => !$exists,
        ], $exists ? 'اسم المستخدم مستخدم بالفعل' : 'اسم المستخدم متاح');
    }

    /**
     * Register a new student.
     */
    public function registerStudent(RegisterStudentRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->registerStudent($request->validated(), $request);

            return $this->successResponse([
                'token' => $result['token'],
                'user' => $result['user']->makeHidden(['id', 'created_at', 'updated_at', 'password']),
            ], 'تم التسجيل بنجاح', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->errorResponse('فشل التسجيل: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Register a new parent.
     */
    public function registerParent(RegisterParentRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->registerParent($request->validated(), $request);

            return $this->successResponse([
                'token' => $result['token'],
                'user' => $result['user']->makeHidden(['id', 'created_at', 'updated_at', 'password']),
            ], 'تم التسجيل بنجاح', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->errorResponse('فشل التسجيل: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Check user existence and 2FA status before login.
     */
    public function loginCheck(LoginCheckRequest $request): JsonResponse
    {
        $result = $this->authService->checkUserForLogin($request->user_name);

        if (!$result) {
            return $this->errorResponse('اسم المستخدم غير موجود', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse($result, 'تم التحقق من المستخدم');
    }

    /**
     * Login with password (for users without 2FA).
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->attemptLogin(
            $request->user_name,
            $request->password,
            $request
        );

        if (!$result['success']) {
            $code = match ($result['reason'] ?? null) {
                'device_blocked', 'max_devices_reached' => Response::HTTP_FORBIDDEN,
                default => Response::HTTP_UNAUTHORIZED,
            };
            return $this->errorResponse($result['message'], $code);
        }

        // If 2FA is required, return token to proceed with OTP
        if ($result['requires_2fa']) {
            $user = User::where('user_name', $request->user_name)->first();
            return $this->successResponse([
                'requires_2fa' => true,
                'phone' => $user->phone ? $this->maskPhone($user->phone) : null,
                'email' => $user->email ? $this->maskEmail($user->email) : null,
            ], 'يرجى إكمال التحقق الثنائي');
        }

        return $this->successResponse([
            'token' => $result['token'],
            'user' => $result['user']->makeHidden(['id', 'created_at', 'updated_at', 'password']),
        ], 'تم تسجيل الدخول بنجاح');
    }

    /**
     * Send OTP for 2FA login or password reset.
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $user = $this->authService->findUserForReset($request->validated());

        if (!$user) {
            return $this->errorResponse('المستخدم غير موجود', Response::HTTP_NOT_FOUND);
        }

        try {
            $result = $this->otpService->generate($user, $request->method);

            return $this->successResponse([
                'token' => $result['token'],
                'expires_in' => $result['expires_in'],
                'method' => $result['method'],
            ], 'تم إرسال رمز التحقق');
        } catch (\Exception $e) {
            return $this->errorResponse('فشل إرسال رمز التحقق', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Verify OTP code.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->otpService->verify($request->token, $request->otp);

        if (!$result['valid']) {
            return $this->errorResponse($result['message'], Response::HTTP_UNAUTHORIZED);
        }

        // For 2FA login flow, complete the login
        $user = User::find($result['user_id']);

        if ($user && $user->hasTwoFactorEnabled()) {
            $loginResult = $this->authService->complete2FALogin($user, $request);

            if (!$loginResult['success']) {
                return $this->errorResponse($loginResult['message'], Response::HTTP_FORBIDDEN);
            }

            // Consume the OTP
            $this->otpService->consume($request->token);

            return $this->successResponse([
                'token' => $loginResult['token'],
                'user' => $loginResult['user']->makeHidden(['id', 'created_at', 'updated_at', 'password']),
            ], 'تم تسجيل الدخول بنجاح');
        }

        // For password reset flow, keep the token valid
        return $this->successResponse([
            'verified' => true,
            'token' => $request->token,
        ], 'تم التحقق من الرمز بنجاح');
    }

    /**
     * Initiate password reset - find user and return contact options.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $user = $this->authService->findUserForReset($request->validated());

        if (!$user) {
            return $this->errorResponse('المستخدم غير موجود', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse([
            'user_found' => true,
            'phone' => $user->phone ? $this->maskPhone($user->phone) : null,
            'email' => $user->email ? $this->maskEmail($user->email) : null,
        ], 'تم العثور على المستخدم، اختر طريقة إرسال رمز التحقق');
    }

    /**
     * Change password after OTP verification.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        // First verify the token is still valid
        $record = DB::table('password_reset_tokens')
            ->where('token', $request->token)
            ->first();

        if (!$record) {
            return $this->errorResponse('رمز التحقق غير صالح أو منتهي الصلاحية', Response::HTTP_UNAUTHORIZED);
        }

        $user = User::find($record->user_id);

        if (!$user) {
            return $this->errorResponse('المستخدم غير موجود', Response::HTTP_NOT_FOUND);
        }

        try {
            $this->authService->changePassword($user, $request->password);
            $this->otpService->consume($request->token);

            return $this->successResponse(null, 'تم تغيير كلمة المرور بنجاح');
        } catch (\Exception $e) {
            return $this->errorResponse('فشل تغيير كلمة المرور', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get current authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->errorResponse('غير مصرح', Response::HTTP_UNAUTHORIZED);
        }

        // Load relationships based on user type
        if ($user->type === 'student') {
            $user->load('student');
        }

        return $this->successResponse([
            'user' => $user->makeHidden(['id', 'created_at', 'updated_at', 'password', 'remember_token']),
            'devices' => $this->deviceService->getUserDevices($user),
        ], 'تم جلب بيانات المستخدم');
    }

    /**
     * Update user profile.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->errorResponse('غير مصرح', Response::HTTP_UNAUTHORIZED);
        }

        $data = $request->validated();

        // Handle password change
        if (isset($data['password'])) {
            if (!Hash::check($data['old_password'], $user->password)) {
                return $this->errorResponse('كلمة المرور القديمة غير صحيحة', Response::HTTP_FORBIDDEN);
            }
            unset($data['old_password']);
        }

        // Handle phone update
        if (isset($data['phone'])) {
            $data['phone'] = ($data['country_code'] ?? '') . $data['phone'];
            unset($data['country_code']);
        }

        try {
            $user->update($data);

            // Update student data if applicable
            if ($user->type === 'student' && $user->student) {
                $studentData = array_intersect_key($data, array_flip(['exam_id', 'exam_date']));
                if (!empty($studentData)) {
                    $user->student->update($studentData);
                }
            }

            return $this->successResponse([
                'user' => $user->fresh()->makeHidden(['id', 'created_at', 'updated_at', 'password', 'remember_token']),
            ], 'تم تحديث الملف الشخصي بنجاح');
        } catch (\Exception $e) {
            return $this->errorResponse('فشل تحديث الملف الشخصي', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Logout (invalidate token).
     */
    public function logout(): JsonResponse
    {
        try {
            /** @var \Tymon\JWTAuth\JWTGuard $guard */
            $guard = auth('api');
            $guard->logout();
            return $this->successResponse(null, 'تم تسجيل الخروج بنجاح');
        } catch (\Exception $e) {
            return $this->errorResponse('فشل تسجيل الخروج', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get user's devices.
     */
    public function devices(): JsonResponse
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->errorResponse('غير مصرح', Response::HTTP_UNAUTHORIZED);
        }

        return $this->successResponse([
            'devices' => $this->deviceService->getUserDevices($user),
        ], 'تم جلب الأجهزة');
    }

    /**
     * Revoke a device.
     */
    public function revokeDevice(int $deviceId): JsonResponse
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->errorResponse('غير مصرح', Response::HTTP_UNAUTHORIZED);
        }

        $deleted = $this->deviceService->revokeDevice($user, $deviceId);

        if (!$deleted) {
            return $this->errorResponse('الجهاز غير موجود', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse(null, 'تم حذف الجهاز');
    }

    /**
     * Mask phone number for privacy.
     */
    private function maskPhone(string $phone): string
    {
        $length = strlen($phone);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }
        return substr($phone, 0, 3) . str_repeat('*', $length - 5) . substr($phone, -2);
    }

    /**
     * Mask email for privacy.
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1] ?? '';

        if (strlen($name) <= 2) {
            $maskedName = str_repeat('*', strlen($name));
        } else {
            $maskedName = $name[0] . str_repeat('*', strlen($name) - 2) . $name[strlen($name) - 1];
        }

        return $maskedName . '@' . $domain;
    }
}
