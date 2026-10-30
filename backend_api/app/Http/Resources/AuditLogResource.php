<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action->value,
            'meta' => $this->meta,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at,
            'user' => $this->whenLoaded('user', fn () => $this->user?->only(['id', 'name', 'badge_id'])),
            'subject' => $this->whenLoaded('subject', fn () => $this->subject?->only(['id', 'case_id', 'name'])),
        ];
    }
}
