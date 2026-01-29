<?php

namespace App\Observers;

use App\Models\School;
use App\Notifications\NewSchoolRegistrationNotification;
use App\Services\AdminNotificationService;

class SchoolObserver
{
    /**
     * Handle the School "created" event.
     */
    public function created(School $school): void
    {
        // Notify all admins about new school registration
        AdminNotificationService::notifyAllAdmins(
            new NewSchoolRegistrationNotification($school)
        );
    }
}
