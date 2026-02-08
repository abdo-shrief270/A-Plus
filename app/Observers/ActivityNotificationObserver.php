<?php

namespace App\Observers;

use App\Models\Admin;
use Filament\Notifications\Notification;
use Spatie\Activitylog\Models\Activity;

class ActivityNotificationObserver
{
    /**
     * Sensitive events that should trigger notifications
     */
    protected array $sensitiveEvents = [
        'deleted' => [
            'App\Models\Student',
            'App\Models\Plan',
            'App\Models\Exam',
            'App\Models\Coupon',
            'App\Models\Admin',
        ],
    ];

    /**
     * Events that should notify all admins (not just super_admin)
     */
    protected array $notifyAllAdmins = [
        'App\Models\Coupon' => ['created', 'updated', 'deleted'],
    ];

    public function created(Activity $activity): void
    {
        $this->handleNotification($activity);
    }

    protected function handleNotification(Activity $activity): void
    {
        $subjectType = $activity->subject_type;
        $event = $activity->event;

        // Check if this is a sensitive delete operation
        if ($event === 'deleted' && in_array($subjectType, $this->sensitiveEvents['deleted'] ?? [])) {
            $this->notifySuperAdmins($activity);
            return;
        }

        // Check if this should notify all admins
        if (isset($this->notifyAllAdmins[$subjectType]) && in_array($event, $this->notifyAllAdmins[$subjectType])) {
            $this->notifyAllAdmins($activity);
        }
    }

    protected function notifySuperAdmins(Activity $activity): void
    {
        $superAdmins = Admin::role('super_admin')->get();
        $modelName = class_basename($activity->subject_type);
        $causerName = $activity->causer?->name ?? 'النظام';

        foreach ($superAdmins as $admin) {
            Notification::make()
                ->title('⚠️ حذف عنصر حساس')
                ->body("قام {$causerName} بحذف {$modelName} رقم #{$activity->subject_id}")
                ->danger()
                ->sendToDatabase($admin);
        }
    }

    protected function notifyAllAdmins(Activity $activity): void
    {
        $admins = Admin::where('active', true)->get();
        $modelName = class_basename($activity->subject_type);
        $causerName = $activity->causer?->name ?? 'النظام';
        $eventArabic = match ($activity->event) {
            'created' => 'إنشاء',
            'updated' => 'تعديل',
            'deleted' => 'حذف',
            default => $activity->event,
        };

        foreach ($admins as $admin) {
            Notification::make()
                ->title("📝 {$eventArabic} {$modelName}")
                ->body("قام {$causerName} بـ{$eventArabic} {$modelName} رقم #{$activity->subject_id}")
                ->info()
                ->sendToDatabase($admin);
        }
    }
}
