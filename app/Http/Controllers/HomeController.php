<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ContactUsRequest;
use App\Models\Contact;
use App\Models\Exam;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    use ApiResponse;
    public function contactUs(ContactUsRequest $request)
    {

        try {
            Contact::create($request->only(['name','email','description']));
            return $this->apiResponse(200, 'Contact Ticket Sent Successfully');
        } catch (\Exception $e) {
            return $this->apiResponse(500, 'Contact Ticket failed: ' . $e->getMessage());
        }
    }
}
