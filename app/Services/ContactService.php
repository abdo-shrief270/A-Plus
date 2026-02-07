<?php

namespace App\Services;

use App\Models\Contact;

class ContactService
{
    /**
     * Submit a contact form.
     *
     * @param array $data
     * @return Contact
     */
    public function submitContact(array $data): Contact
    {
        return Contact::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'description' => $data['description'],
        ]);
    }
}
