<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\v2\ContactReplyResource;
use App\Http\Resources\v2\ContactResource;
use App\Models\Contact;
use App\Models\ContactAttachment;
use App\Models\ContactReply;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TicketController extends BaseApiController
{
    /**
     * List My Tickets (رسائل التواصل)
     *
     * يجلب قائمة برسائل التواصل التي أرسلها المستخدم الحالي.
     *
     * @queryParam status string optional `open\|pending\|resolved\|closed`. Example: open
     * @queryParam per_page integer optional Default 15
     *
     * @group Support / Tickets (رسائل التواصل)
     * @unauthenticated false
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $query = Contact::query()
            ->where('user_id', $user->id)
            ->withCount('replies')
            ->orderByDesc('last_reply_at')
            ->orderByDesc('created_at');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $tickets = $query->paginate($request->input('per_page', 15));

        return $this->successResponse(
            ContactResource::collection($tickets)->response()->getData(true),
            'Tickets retrieved successfully'
        );
    }

    /**
     * Get Ticket Detail (تفاصيل الرسالة)
     *
     * @group Support / Tickets (رسائل التواصل)
     * @unauthenticated false
     */
    public function show(Contact $ticket): JsonResponse
    {
        $user = auth('api')->user();
        if ($ticket->user_id !== $user->id) {
            return $this->errorResponse('غير مصرح بالوصول إلى هذه الرسالة', 403);
        }

        $ticket->load(['attachments', 'replies.user', 'replies.attachments']);

        return $this->successResponse(
            new ContactResource($ticket),
            'Ticket retrieved successfully'
        );
    }

    /**
     * Create New Ticket (إرسال رسالة تواصل جديدة)
     *
     * @bodyParam subject string required موضوع الرسالة. Example: استفسار عن الاشتراك
     * @bodyParam description string required نص الرسالة. Example: لدي سؤال حول كيفية تجديد الاشتراك...
     * @bodyParam category string optional `inquiry\|complaint\|suggestion\|technical\|billing\|question_report\|other`. Example: inquiry
     *
     * @group Support / Tickets (رسائل التواصل)
     * @unauthenticated false
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string|min:5|max:5000',
            'category' => 'sometimes|in:inquiry,complaint,suggestion,technical,billing,question_report,other',
            'attachments' => 'sometimes|array|max:10',
            'attachments.*' => 'image|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ]);

        $user = auth('api')->user();

        $ticket = Contact::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email ?? '',
            'subject' => $request->input('subject'),
            'description' => $request->input('description'),
            'status' => 'open',
            'category' => $request->input('category', 'inquiry'),
        ]);

        $this->storeAttachments($request->file('attachments', []), $ticket);
        $ticket->load('attachments');

        return $this->successResponse(
            new ContactResource($ticket),
            'Ticket created successfully',
            201
        );
    }

    /**
     * Reply to Ticket (الرد على رسالة)
     *
     * @bodyParam body string required نص الرد. Example: شكراً لتواصلكم...
     *
     * @group Support / Tickets (رسائل التواصل)
     * @unauthenticated false
     */
    public function reply(Request $request, Contact $ticket): JsonResponse
    {
        $request->validate([
            'body' => 'required|string|min:1|max:5000',
            'attachments' => 'sometimes|array|max:10',
            'attachments.*' => 'image|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ]);

        $user = auth('api')->user();
        if ($ticket->user_id !== $user->id) {
            return $this->errorResponse('غير مصرح بالرد على هذه الرسالة', 403);
        }

        if (in_array($ticket->status, ['closed'], true)) {
            return $this->errorResponse('هذه الرسالة مغلقة، لا يمكن إضافة ردود', 422);
        }

        $reply = ContactReply::create([
            'contact_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => $request->input('body'),
            'is_staff' => false,
        ]);

        $this->storeAttachments($request->file('attachments', []), $reply);

        $ticket->forceFill([
            'last_reply_at' => now(),
            'status' => $ticket->status === 'resolved' ? 'open' : $ticket->status,
        ])->save();

        $reply->load('user', 'attachments');

        return $this->successResponse(
            new ContactReplyResource($reply),
            'Reply posted successfully',
            201
        );
    }

    /**
     * Persist a list of UploadedFile objects to the public disk and link them
     * to the given Contact or ContactReply via the polymorphic attachable.
     */
    protected function storeAttachments(array|null $files, Model $attachable): void
    {
        if (!$files) return;

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) continue;
            $path = $file->store("contact_attachments/{$attachable->id}", 'public');
            ContactAttachment::create([
                'attachable_type' => get_class($attachable),
                'attachable_id' => $attachable->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }

    /**
     * Close Ticket (إغلاق الرسالة)
     *
     * @group Support / Tickets (رسائل التواصل)
     * @unauthenticated false
     */
    public function close(Contact $ticket): JsonResponse
    {
        $user = auth('api')->user();
        if ($ticket->user_id !== $user->id) {
            return $this->errorResponse('غير مصرح', 403);
        }

        $ticket->forceFill(['status' => 'closed'])->save();

        return $this->successResponse(
            new ContactResource($ticket->fresh()),
            'Ticket closed'
        );
    }
}
