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
     * Submit Contact Form (نموذج اتصل بنا)
     *
     * يقوم بتسجيل وإرسال رسالة تواصل من الواجهة الأمامية أو التطبيق إلى لوحة تحكم الإدارة.
     *
     * @bodyParam type string required نوع الرسالة (`complaint`, `suggestion`, `inquiry`). Example: inquiry
     * @bodyParam name string required اسم المُرسل. Example: عبدالله أحمد
     * @bodyParam email string optional البريد الإلكتروني (إن وُجد). Example: test@test.com
     * @bodyParam phone string optional الهاتف رقم (إن وُجد). Example: 0110000000
     * @bodyParam message string required نص الرسالة بحد أقصى 1000 حرف. Example: أواجه مشكلة في تسجيل الدخول.
     *
     * @group Support & Settings (الإعدادات والدعم)
     * @unauthenticated
     *
     * @response 201 array{status: int, message: string, data: array}
     * @response 422 array{status: int, message: string, errors: array}
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
