<?php

namespace App\Http\Resources\v2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'content' => $this->content,
            'icon' => $this->icon,
            'is_published' => (bool) $this->is_published,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
