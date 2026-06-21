<?php

namespace App\Services\Auth;

use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;

class DeviceService
{
    /**
     * Maximum number of devices allowed per user.
     */
    protected int $maxDevices = 5;

    /**
     * Register or update a device for a user.
     */
    public function registerDevice(User $user, Request $request): Device
    {
        $deviceId = $this->extractDeviceId($request);

        if (!$deviceId) {
            throw new \InvalidArgumentException('Device ID is required');
        }

        $existing = $user->devices()->where('device_id', $deviceId)->first();

        // Re-registration of an already-known device — only refresh metadata,
        // never change approval state.
        if ($existing) {
            $existing->update([
                'name' => $request->header('X-Device-Name'),
                'platform' => $request->header('X-Device-Platform'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_login_at' => now(),
            ]);
            return $existing;
        }

        $isFirstDevice = $user->devices()->count() === 0;

        if (!$isFirstDevice) {
            // SECURITY: a brand-new device tried to join an existing account.
            // Lock every device for this user (including the previously
            // current one) so the legitimate owner has to confirm with the
            // admin before *anything* is usable again. Admin sees the new
            // pending device + the activity log entry, decides what
            // happened, and re-approves whichever device they trust.
            $user->devices()->update([
                'is_approved' => false,
                'is_current' => false,
            ]);

            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->event('new_device_attempt')
                ->withProperties([
                    'device_id' => $deviceId,
                    'name' => $request->header('X-Device-Name'),
                    'platform' => $request->header('X-Device-Platform'),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
                ->log('محاولة تسجيل دخول من جهاز جديد — تم قفل كل الأجهزة بانتظار مراجعة الإدارة');
        }

        return Device::create([
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'name' => $request->header('X-Device-Name'),
            'platform' => $request->header('X-Device-Platform'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_login_at' => now(),
            'is_trusted' => true,
            'is_approved' => $isFirstDevice,
            'is_current' => $isFirstDevice,
        ]);
    }

    /**
     * Promote the given device to be the user's "current" session, demoting
     * every other device. Call this only after credentials AND approval
     * checks have passed.
     */
    public function promoteCurrent(User $user, string $deviceId): void
    {
        $user->devices()
            ->where('device_id', '!=', $deviceId)
            ->update(['is_current' => false]);

        $user->devices()
            ->where('device_id', $deviceId)
            ->update(['is_current' => true, 'last_login_at' => now()]);
    }

    /**
     * Validate if the device is allowed for login.
     */
    public function validateDevice(User $user, Request $request): array
    {
        $deviceId = $this->extractDeviceId($request);

        if (!$deviceId) {
            return [
                'allowed' => false,
                'reason' => 'device_id_required',
                'message' => 'معرف الجهاز مطلوب للمتابعة',
            ];
        }

        $device = $user->devices()->where('device_id', $deviceId)->first();

        // Device exists and is trusted
        if ($device && $device->is_trusted) {
            // Check approval status
            if (!$device->is_approved) {
                return [
                    'allowed' => false,
                    'reason' => 'device_pending_approval',
                    'message' => 'هذا الجهاز قيد المراجعة من قبل الإدارة. يرجى الانتظار حتى يتم تفعيله.',
                ];
            }

            return [
                'allowed' => true,
                'device' => $device,
                'is_new' => false,
            ];
        }

        // Device exists but not trusted (blocked by admin)
        if ($device && !$device->is_trusted) {
            return [
                'allowed' => false,
                'reason' => 'device_blocked',
                'message' => 'هذا الجهاز محظور. يرجى التواصل مع الدعم.',
            ];
        }

        // New device - check if user reached max devices
        $deviceCount = $user->devices()->count();

        if ($deviceCount >= $this->maxDevices) {
            return [
                'allowed' => false,
                'reason' => 'max_devices_reached',
                'message' => 'لقد وصلت للحد الأقصى من الأجهزة المسموح بها. يرجى حذف جهاز آخر أولاً.',
            ];
        }

        // New device, allowed - will be registered on successful login
        return [
            'allowed' => true,
            'device' => null,
            'is_new' => true,
        ];
    }

    /**
     * Extract device ID from request.
     */
    public function extractDeviceId(Request $request): ?string
    {
        return $request->header('X-Device-ID') ?? $request->input('device_id');
    }

    /**
     * Revoke (delete) a device.
     */
    public function revokeDevice(User $user, int $deviceId): bool
    {
        return $user->devices()->where('id', $deviceId)->delete() > 0;
    }

    /**
     * Block a device (keep record but mark as untrusted).
     */
    public function blockDevice(Device $device): bool
    {
        return $device->update(['is_trusted' => false]);
    }

    /**
     * Unblock a device.
     */
    public function unblockDevice(Device $device): bool
    {
        return $device->update(['is_trusted' => true]);
    }

    /**
     * Get all devices for a user.
     */
    public function getUserDevices(User $user)
    {
        return $user->devices()->orderByDesc('last_login_at')->get();
    }
}
