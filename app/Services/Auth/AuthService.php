<?php

namespace App\Services\Auth;

use App\Models\Device;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(
        protected DeviceService $deviceService,
        protected OtpService $otpService
    ) {
    }

    /**
     * Register a new student.
     */
    public function registerStudent(array $data, Request $request): array
    {
        return DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'name' => $data['name'],
                'user_name' => $data['user_name']?? $this->generateUniqueUserName($data['name']),
                'phone' => ($data['country_code'] ?? '') . $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'type' => 'student',
                'gender' => $data['gender'],
            ]);

            Student::create([
                'user_id' => $user->id,
                'exam_id' => $data['exam_id'],
                'exam_date' => $data['exam_date'] ?? null,
            ]);

            // Register device on registration
            $device = $this->deviceService->registerDevice($user, $request);

            $token = $this->generateToken($user);

            return [
                'user' => $user->load('student'),
                'token' => $token,
                'device' => $device,
            ];
        });
    }

    /**
     * Register a new parent.
     */
    public function registerParent(array $data, Request $request): array
    {
        return DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'name' => $data['name'],
                'user_name' => $data['user_name']?? $this->generateUniqueUserName($data['name']),
                'phone' => ($data['country_code'] ?? '') . $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'type' => 'parent',
                'gender' => $data['gender'],
            ]);

            // Register device on registration
            $device = $this->deviceService->registerDevice($user, $request);

            $token = $this->generateToken($user);

            return [
                'user' => $user,
                'token' => $token,
                'device' => $device,
            ];
        });
    }

    /**
     * Check if username exists and return 2FA status.
     */
    public function checkUserForLogin(string $userName): ?array
    {
        $user = User::where('user_name', $userName)->first();

        if (!$user) {
            return null;
        }

        return [
            'exists' => true,
            'has_2fa' => $user->hasTwoFactorEnabled(),
            'phone' => $user->phone ? $this->maskPhone($user->phone) : null,
            'email' => $user->email ? $this->maskEmail($user->email) : null,
        ];
    }

    /**
     * Attempt login with password.
     */
    public function attemptLogin(string $userName, string $password, Request $request): array
    {
        $user = User::where('user_name', $userName)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return [
                'success' => false,
                'message' => 'بيانات الاعتماد غير صحيحة',
            ];
        }

        if (!$user->active) {
            return [
                'success' => false,
                'message' => 'حسابك معطل. يرجى التواصل مع الدعم.',
            ];
        }

        // Validate device
        $deviceCheck = $this->deviceService->validateDevice($user, $request);

        if (!$deviceCheck['allowed']) {
            return [
                'success' => false,
                'message' => $deviceCheck['message'],
                'reason' => $deviceCheck['reason'],
            ];
        }

        // If 2FA is enabled, don't return token yet
        if ($user->hasTwoFactorEnabled()) {
            return [
                'success' => true,
                'requires_2fa' => true,
                'user_id' => $user->id,
            ];
        }

        // Register device if new
        if ($deviceCheck['is_new']) {
            $device = $this->deviceService->registerDevice($user, $request);

            if (!$device->is_approved) {
                return [
                    'success' => false,
                    'message' => 'هذا الجهاز قيد المراجعة من قبل الإدارة. يرجى الانتظار حتى يتم تفعيله.',
                    'reason' => 'device_pending_approval',
                ];
            }
        } else {
            $deviceCheck['device']->updateLastLogin();
        }

        $token = $this->generateToken($user);

        return [
            'success' => true,
            'requires_2fa' => false,
            'token' => $token,
            'user' => $user,
        ];
    }

    /**
     * Complete 2FA login.
     */
    public function complete2FALogin(User $user, Request $request): array
    {
        $deviceCheck = $this->deviceService->validateDevice($user, $request);

        if (!$deviceCheck['allowed']) {
            return [
                'success' => false,
                'message' => $deviceCheck['message'],
            ];
        }

        if ($deviceCheck['is_new']) {
            $device = $this->deviceService->registerDevice($user, $request);

            if (!$device->is_approved) {
                return [
                    'success' => false,
                    'message' => 'هذا الجهاز قيد المراجعة من قبل الإدارة. يرجى الانتظار حتى يتم تفعيله.',
                ];
            }
        } else {
            $deviceCheck['device']->updateLastLogin();
        }

        $token = $this->generateToken($user);

        return [
            'success' => true,
            'token' => $token,
            'user' => $user,
        ];
    }

    /**
     * Find user for password reset.
     */
    public function findUserForReset(array $data): ?User
    {
        if (!empty($data['user_name'])) {
            return User::where('user_name', $data['user_name'])->first();
        }

        if (!empty($data['email'])) {
            return User::where('email', $data['email'])->first();
        }

        if (!empty($data['phone'])) {
            $phone = ($data['country_code'] ?? '') . $data['phone'];
            return User::where('phone', $phone)->first();
        }

        return null;
    }

    /**
     * Change user password.
     */
    public function changePassword(User $user, string $newPassword): bool
    {
        return $user->update(['password' => $newPassword]);
    }

    /**
     * Generate JWT token.
     */
    protected function generateToken(User $user): string
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        return $guard->login($user);
    }

    /**
     * Mask phone number for display.
     */
    protected function maskPhone(string $phone): string
    {
        $length = strlen($phone);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }
        return substr($phone, 0, 3) . str_repeat('*', $length - 5) . substr($phone, -2);
    }

    /**
     * Mask email for display.
     */
    protected function maskEmail(string $email): string
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

    protected function generateUniqueUserName($name) : String
    {
        $base = Str::slug($name);

        do {
            $random = Str::lower(Str::random(5));
            $username = "{$base}-{$random}";
        } while (User::where('user_name', $username)->exists());

        return $username;
    }
}
