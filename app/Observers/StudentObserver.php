<?php

namespace App\Observers;

use App\Models\Student;
use App\Notifications\NewStudentRegistrationNotification;
use App\Services\AdminNotificationService;
use App\Services\TrialService;

class StudentObserver
{
    /**
     * Handle the Student "created" event.
     */
    public function created(Student $student): void
    {
        // Grant a free trial so every new student (self-registered, added by a
        // parent/school, or imported) gets full access for the trial window.
        app(TrialService::class)->grantTo($student);

        // Notify all admins about new student registration
        AdminNotificationService::notifyAllAdmins(
            new NewStudentRegistrationNotification($student)
        );
    }
}
