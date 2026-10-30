<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InfractionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject_id' => $this->subject_id,
            'subject' => $this->whenLoaded('subject', fn () => $this->subject ? [
                'id' => $this->subject->id,
                'case_id' => $this->subject->case_id,
                'name' => $this->subject->name,
            ] : null),
            'type' => $this->whenLoaded('type', fn () => [
                'id' => $this->type->id,
                'code' => $this->type->code,
                'name' => $this->type->name,
                'severity' => $this->type->severity->value,
            ]),
            'manhunt_code' => $this->whenLoaded('manhunt', fn () => $this->manhunt?->code),
            'occurred_on' => $this->occurred_on?->toDateString(),
            'location' => $this->location,
            'disposition' => $this->disposition,
            'fine_amount' => $this->fine_amount,
            'bounty_value' => $this->bounty_value,
            'served' => $this->served_at !== null,
            'served_at' => $this->served_at,
            'fine_paid' => $this->fine_paid_at !== null,
            'fine_paid_at' => $this->fine_paid_at,
        ];
    }
}
