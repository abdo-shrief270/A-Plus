<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Notifications\Notification;

class AdminNotificationService
{
    /**
     * Send notification to all admins
     */
    public static function notifyAllAdmins(Notification $notification): void
    {
        $admins = Admin::where('active', true)->get();

        foreach ($admins as $admin) {
            $admin->notify($notification);
        }
    }

    /**
     * Send notification to super admins only
     */
    public static function notifySuperAdmins(Notification $notification): void
    {
        $superAdmins = Admin::where('active', true)
            ->role('مدير النظام')
            ->get();

        foreach ($superAdmins as $admin) {
            $admin->notify($notification);
        }
    }

    /**
     * Send notification to admins with specific role
     */
    public static function notifyAdminsByRole(string $role, Notification $notification): void
    {
        $admins = Admin::where('active', true)
            ->role($role)
            ->get();

        foreach ($admins as $admin) {
            $admin->notify($notification);
        }
    }
}
