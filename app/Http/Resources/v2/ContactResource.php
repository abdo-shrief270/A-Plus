<?php

namespace App\Http\Resources\v2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'email' => $this->email,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status ?? 'open',
            'category' => $this->category ?? 'inquiry',
            'replies_count' => $this->replies_count ?? null,
            'last_reply_at' => $this->last_reply_at?->toIso8601String(),
            'replies' => ContactReplyResource::collection($this->whenLoaded('replies')),
            'attachments' => ContactAttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
