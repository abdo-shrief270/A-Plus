<?php

namespace App\Observers;

use App\Models\Contact;
use App\Notifications\NewContactNotification;
use App\Services\AdminNotificationService;

class ContactObserver
{
    /**
     * Handle the Contact "created" event.
     */
    public function created(Contact $contact): void
    {
        // Notify all admins about new contact message
        AdminNotificationService::notifyAllAdmins(
            new NewContactNotification($contact)
        );
    }
}
