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
     * Check Username Availability
     *
     * تتحقق هذه النهاية الطرفية مما إذا كان اسم المستخدم المقترح متاحًا للتسجيل أم لا.
     * يمكن للواجهة الأمامية استخدام هذا المسار (Endpoint) ليتم استدعاؤه بشكل ديناميكي أثناء كتابة المستخدم لاسم لتقديم ملاحظات فورية.
     * 
     * @bodyParam user_name string required اسم المستخدم الذي يجب التحقق منه. يجب ألا يحتوي على مسافات، فقط أحرف، أرقام، وشرطات سفلية. Example: ahmed_ali123
     *
     * @group Authentication (المصادقة)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{available: bool}}
     */
    public function checkUsername(CheckUsernameRequest $request): JsonResponse
    {
        $exists = User::where('user_name', $request->user_name)->exists();

        return $this->successResponse([
            'available' => !$exists,
        ], $exists ? 'اسم المستخدم مستخدم بالفعل' : 'اسم المستخدم متاح');
    }

    /**
     * Register Student
     *
     * يقوم بتسجيل حساب طالب جديد في المنصة. 
     * يجب على الواجهة الأمامية تقديم جميع البيانات الإلزامية مثل الاسم، واسم المستخدم، وكلمة المرور.
     * بالإضافة إلى ذلك، يجب ربط الطالب بامتحان معين (`exam_id`).
     * 
     * @bodyParam name string required اسم الطالب بالكامل. Example: أحمد علي
     * @bodyParam user_name string required اسم المستخدم (فريد). يجب استخدام مسار Check Username للتأكد من توفره. Example: ahmed_ali
     * @bodyParam phone string optional رقم هاتف الطالب للاتصال به (لحالات الـ OTP لاحقاً). Example: 01012345678
     * @bodyParam gender string required جنس الطالب (`male` أو `female`). Example: male
     * @bodyParam password string required كلمة المرور (يجب أن تحتوي على 8 أحرف على الأقل، حرفيات، وأرقام). Example: Password123!
     * @bodyParam password_confirmation string required تأكيد كلمة المرور المطابق لـ password. Example: Password123!
     * @bodyParam exam_id integer required معرف الامتحان (الصف/المرحلة الدراسية) الذي يسجل فيه الطالب. Example: 1
     * @bodyParam fcm_token string optional رمز Firebase لإرسال الإشعارات لهاتف المستخدم. Example: cksb...
     *
     * @group Authentication (المصادقة)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{token: string, user: \App\Models\User}}
     * @response 422 array{status: int, message: string, errors: array} - أخطاء التحقق من البيانات
     */
    public function registerStudent(RegisterStudentRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->registerStudent($request->validated(), $request);

            return $this->successResponse([
                'token' => $result['token'],
                'user' => $result['user']->makeHidden(['id', 'created_at', 'updated_at', 'password']),
            ], 'تم التسجيل بنجاح', Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->errorResponse('فشل التسجيل: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Register Parent
     *
     * Registers a new parent user.
     *
     * @group Authentication
     * @unauthenticated
     *
     * @response array{status: int, message: string, data: array{token: string, user: \App\Models\User}}
     */
    public function registerParent(RegisterParentRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->registerParent($request->validated(), $request);

            return $this->successResponse([
                'token' => $result['token'],
                'user' => $result['user']->makeHidden(['id', 'created_at', 'updated_at', 'password']),
            ], 'تم التسجيل بنجاح', Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->errorResponse('فشل التسجيل: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Pre-Login Check (التحقق قبل تسجيل الدخول)
     *
     * يتحقق هذا المسار من وجود اسم المستخدم المعطى وما إذا كان هذا الحساب يلزمه تفعيل المصادقة الثنائية (2FA) قبل إتمام تسجيل الدخول.
     * مفيد لتقسيم واجهة تسجيل الدخول إلى خطوات مستقلة.
     * 
     * @bodyParam user_name string required اسم المستخدم المسجل في النظام المراد التحقق منه. Example: ahmed_ali
     *
     * @group Authentication (المصادقة)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{exists: bool, requires_2fa: bool}}
     * @response 404 array{status: int, message: string} - إذا لم يكن اسم المستخدم موجوداً
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
     * Login Authentication (تسجيل الدخول)
     *
     * يصادق المستخدم باستخدام (اسم المستخدم) و (كلمة المرور).
     * يحتوي هذا المسار أيضاً على فحص للأجهزة المسجلة (Device Management)، حيث سيتم ربط الجهاز الجديد بحساب المستخدم. 
     * إذا اكتشف النظام أن المستخدم يسجل من جهاز ثانٍ تجاوز الحد المسموح به، فسيتم إرجاع خطأ `403` بأن الجهاز بانتظار موافقة الإدارة (Pending Approval).
     * 
     * إذا كان النظام يتطلب 2FA، فسيقوم الحقل `requires_2fa: true` بالرجوع بدون الـ JWT token، لبدء مسار الـ OTP.
     * 
     * @bodyParam user_name string required اسم المستخدم. Example: ahmed_ali
     * @bodyParam password string required كلمة المرور المرتبطة بهذا الحساب. Example: Password123!
     * @bodyParam device_name string optional اسم الجهاز المراد تسجيله (مثال: iPhone 14 Pro). Example: iPhone 13
     * @bodyParam device_id string optional المعرف الفريد للجهاز (UUID). ضروري لتتبع الأجهزة المسجلة والموافقة عليها. Example: 1234-uuid-abcd
     * @bodyParam fcm_token string optional رمز الاستجابة لإشعارات Firebase الخاصة بالجهاز الدخيل. Example: dummy_fcm_code
     *
     * @group Authentication (المصادقة)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{token: string, user: \App\Models\User}}
     * @response 200 array{status: int, message: string, data: array{requires_2fa: true, phone: string, email: string}}
     * @response 401 array{status: int, message: string} - بيانات غير صحيحة
     * @response 403 array{status: int, message: string} - الجهاز بانتظار موافقة أو محظور (Pending admin approval)
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
     * Send OTP Code (إرسال رمز التحقق)
     *
     * يرسل رمز تحقق مكون من 6 أرقام (OTP) إلى الهاتف أو البريد الإلكتروني.
     * يتم استخدام هذا المسار لإتمام إجراءات المصادقة الثنائية (2FA) أو استعادة كلمة المرور (Password Reset).
     * يجب على الواجهة الأمامية حفظ الـ `token` الذي سيتم إرجاعه لأنه يُستخدم لاحقاً مع مسار `verifyOtp`.
     * 
     * @bodyParam user_name string required اسم المستخدم المراد إرسال الرمز المرتبط به (في حال استعادة كلمة المرور). Example: ahmed_ali
     * @bodyParam method string required طريقة إرسال الرمز: 'sms' للرسائل القصيرة، 'whatsapp' للواتساب، 'email' للبريد الإلكتروني. Example: sms
     *
     * @group Authentication (المصادقة)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{token: string, expires_in: int, method: string}}
     * @response 404 array{status: int, message: string} - في حال لم يسجل المستخدم
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $user = $this->authService->findUserForReset($request->validated());

        if (!$user) {
            return $this->errorResponse('المستخدم غير موجود', Response::HTTP_NOT_FOUND);
        }

        try {
            $result = $this->otpService->generate($user, $request->get('method'));

            return $this->successResponse([
                'token' => $result['token'],
                'expires_in' => $result['expires_in'],
                'method' => $result['method'],
            ], 'تم إرسال رمز التحقق');
        } catch (\Exception $e) {
            logger()->error('Error sending OTP: ' . $e->getMessage());
            return $this->errorResponse('فشل إرسال رمز التحقق', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Verify OTP Code (التحقق من رمز OTP)
     *
     * يتم إرسال الرمز المرسل من المستخدم (`otp`) مع الـ (`token`) الذي تم إرجاعه من مسار الإرسال `sendOtp`.
     * إذا كان السياق هو استرجاع كلمة مرور `type=reset`، فسيرجع المسار مفتاحاً مؤكداً لعملية تغيير الكلمة `verified=true`.
     * أما إذا كان سياق المستخدم `type=login`، فسيُرجع المسار مباشرة الـ JWT لتسجيل الدخول النهائي.
     * 
     * @bodyParam token string required مفتاح التحقق الذي تم إرجاعه من خطوة `sendOtp`. Example: otp-token-uuid
     * @bodyParam otp string required رمز الـ OTP المكون من 6 أرقام المدخل من المستخدم. Example: 123456
     * @bodyParam type string optional نوع التحقق، استخدم `login` لاستكمال تسجيل الدخول، أو `reset` لاسترجاع كلمة المرور. قيم أخرى ستفترض أنها مسار دخول عادي. Example: login
     *
     * @group Authentication (المصادقة)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{token: string, user: \App\Models\User}} - (في حالة تسجيل دخول 2FA)
     * @response 200 array{status: int, message: string, data: array{verified: bool, token: string}} - (مفتاح مؤكد لعمليات تغيير كلمة المرور)
     * @response 401 array{status: int, message: string} - الكود خاطئ أو منتهي الصلاحية
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->otpService->verify($request->token, $request->otp);

        if (!$result['valid']) {
            return $this->errorResponse($result['message'], Response::HTTP_UNAUTHORIZED);
        }

        $user = User::find($result['user_id']);

        // Check if this is a login attempt
        // We login if the type is explicitly 'login'
        // OR if type is not 'reset' AND user has 2FA enabled (default behavior)
        $shouldLogin = $request->type === 'login' ||
                      ($request->type !== 'reset' && $user && $user->hasTwoFactorEnabled());

        if ($shouldLogin && $user) {
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

        // For explicitly 'reset' or non-2FA flows, verifying is enough
        return $this->successResponse([
            'verified' => true,
            'token' => $request->token,
        ], 'تم التحقق من الرمز بنجاح');
    }

    /**
     * Reset Password Init (بدء استرجاع كلمة المرور)
     *
     * الخطوة الأولى لاسترجاع المفقود، يقوم النظام بالبحث عن المستخدم بناءً على الحقل المدخل (user_name، أو email، أو phone)
     * ويرجع للواجهة الأمامية أجزاء مخفية من البريد أو الهاتف (Masked) ليقوم المستخدم باختيار أي وسيلة استقبال كود الـ OTP يرغب بها باستخدام مسار الإرسال `sendOtp`.
     * 
     * @bodyParam user_name string required اسم المستخدم المفقود كلمة مروره. يمكن تمرير الهاتف أو الإيميل مكانه وسيتم البحث عنهما أيضاً. Example: ahmed_ali
     *
     * @group Authentication (المصادقة)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: array{user_found: bool, phone: ?string, email: ?string}}
     * @response 404 array{status: int, message: string} - لم يتم العثور على الحساب
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
     * Change Password After OTP (تغيير كلمة المرور الجديدة)
     *
     * يتيح للمستخدم ضبط كلمة مرور جديدة بعد إتمامه لعملية التحقق وإرسال مفتاح الـ `token` المعادل (Verified) من خطوة `verifyOtp`.
     * 
     * @bodyParam token string required مفتاح التحقق المصدق بنجاح في الخطوة السابقة. Example: otp-token-uuid
     * @bodyParam password string required كلمة المرور الجديدة القوية. Example: NewPassword123!
     * @bodyParam password_confirmation string required تأكيد كلمة المرور الجديدة لتجنب الأخطاء المطبعية. Example: NewPassword123!
     *
     * @group Authentication (المصادقة)
     * @unauthenticated
     *
     * @response 200 array{status: int, message: string, data: null} - تم التغيير بنجاح
     * @response 401 array{status: int, message: string} - رمز موثوقية غير صالح
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

        // Check if OTP was verified
        if (empty($record->verified_at)) {
            return $this->errorResponse('يجب التحقق من رمز OTP أولاً', Response::HTTP_FORBIDDEN);
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
     * Get Current Profile (حسابي)
     *
     * يجلب بيانات المستخدم المسجل دخوله حالياً باستخدام الـ Token المرسل في הـ Header.
     * تُرجع هذه النهاية بيانات المستخدم الأساسية، بالإضافة إلى سجل الأجهزة المرتبطة بحسابه (`devices`).
     * إذا كان المستخدم طالباً، سيتم أيضاً تضمين كائن `student` الذي يشمل بيانات المرحلة الدراسية.
     *
     * @group Authentication (المصادقة)
     *
     * @response 200 array{status: int, message: string, data: array{user: \App\Models\User, devices: array}}
     * @response 401 array{status: int, message: string} - يتطلب إرسال Authorization Header
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
     * Update Profile (تحديث الملف الشخصي)
     *
     * يتيح للمستخدم تحديث بيانات حسابه. جميع الحقول اختيارية، ويتم تحديث ما يُرسل فقط.
     * في حال الرغبة بتغيير كلمة المرور، يجب إرسال `password` الجديدة مع إرسال `old_password`.
     * بالنسبة للطلاب، يمكنهم أيضاً تحديث `exam_id` أو `exam_date` الخاصة بالمرحلة الدراسية من خلال هذا المسار.
     * 
     * @bodyParam name string optional الاسم الكامل الجديد. Example: أحمد علي
     * @bodyParam phone string optional رقم الهاتف الجديد. Example: 01012345678
     * @bodyParam country_code string optional كود الدولة للهاتف في حال تواجده. Example: +20
     * @bodyParam email string optional البريد الإلكتروني. Example: user@example.com
     * @bodyParam password string optional كلمة المرور الجديدة. Example: NewPassword123!
     * @bodyParam old_password string optional كلمة المرور الحالية (مطلوبة فقط إذا تم إرسال password للتبديل). Example: OldPass123!
     * @bodyParam exam_id integer optional لتحديث الصف الدراسي للطالب. Example: 2
     *
     * @group Authentication (المصادقة)
     *
     * @response 200 array{status: int, message: string, data: array{user: \App\Models\User}}
     * @response 401 array{status: int, message: string}
     * @response 403 array{status: int, message: string} - كلمة المرور القديمة غير صحيحة
     * @response 422 array{status: int, message: string, errors: array}
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
     * Logout Securely (تسجيل الخروج)
     *
     * يبطل صلاحية الـ JWT Token الحالي بشكل فوري لمنع أي استخدام مستقبلي له.
     * مفيد لإنهاء الجلسة وتسجيل الخروج من الجهاز المعني.
     *
     * @group Authentication (المصادقة)
     *
     * @response 200 array{status: int, message: string, data: null} - تم تسجيل الخروج بنجاح
     * @response 500 array{status: int, message: string} - خطأ داخلي
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
     * Get Registered Devices (أجهزتي المعرفة)
     *
     * يجلب قائمة بالأجهزة التي سجل المستخدم الدخول منها.
     * مفيد لعرض "إدارة الأجهزة" داخل واجهة التطبيق، حيث يظهر لكل جهاز اسمه (مثل iPhone) وآخر نشاط له، وحالته (موافق عليه، محظور، بانتظار الموافقة).
     *
     * @group Authentication / Devices (الأجهزة)
     *
     * @response 200 array{status: int, message: string, data: array{devices: array}}
     * @response 401 array{status: int, message: string}
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
     * Revoke / Remove Device (حذف جهاز نشط)
     *
     * يتيح للمستخدم طرد / إزالة جهاز معين من قائمة أجهزته النشطة.
     * سيتم فوراً منع الجهاز المحذوف من استخدام الـ Token الخاصة به (إذا كانت مخزنة)، وسجل الجهاز سيُمسح من قاعدة البيانات.
     *
     * @group Authentication / Devices (الأجهزة)
     *
     * @response 200 array{status: int, message: string, data: null} - تم إزالة الجهاز
     * @response 404 array{status: int, message: string} - الجهاز المذكور غير موجود
     * @response 401 array{status: int, message: string}
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
