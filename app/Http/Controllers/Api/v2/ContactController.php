<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\v2\ContactRequest;
use App\Http\Resources\v2\ContactResource;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;

class ContactController extends BaseApiController
{
    protected ContactService $contactService;

    public function __construct(ContactService $contactService)
    {
        $this->contactService = $contactService;
    }

    /**
     * Submit a contact form.
     *
     * @param ContactRequest $request
     * @return JsonResponse
     */
    public function store(ContactRequest $request): JsonResponse
    {
        try {
            $contact = $this->contactService->submitContact($request->validated());

            return $this->successResponse(
                ['contact' => new ContactResource($contact)],
                'Contact message submitted successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to submit contact message',
                500
            );
        }
    }
}
