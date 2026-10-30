<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusChangeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from' => $this->from_status?->value,
            'to' => $this->to_status->value,
            'reason' => $this->reason,
            'changed_by' => $this->whenLoaded('changer', fn () => $this->changer?->only(['id', 'name', 'badge_id'])),
            'created_at' => $this->created_at,
        ];
    }
}
