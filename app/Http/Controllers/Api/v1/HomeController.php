<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ContactUsRequest;
use App\Models\Contact;
use App\Models\Exam;
// use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;

class HomeController extends BaseApiController
{
    // use ApiResponse;
    public function contactUs(ContactUsRequest $request)
    {

        try {
            Contact::create($request->only(['name', 'email', 'description']));
            return $this->successResponse(null, 'Contact Ticket Sent Successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Contact Ticket failed: ' . $e->getMessage(), 500);
        }
    }
}
