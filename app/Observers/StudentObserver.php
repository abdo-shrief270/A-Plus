<?php

namespace App\Observers;

use App\Models\Student;
use App\Notifications\NewStudentRegistrationNotification;
use App\Services\AdminNotificationService;

class StudentObserver
{
    /**
     * Handle the Student "created" event.
     */
    public function created(Student $student): void
    {
        // Notify all admins about new student registration
        AdminNotificationService::notifyAllAdmins(
            new NewStudentRegistrationNotification($student)
        );
    }
}
