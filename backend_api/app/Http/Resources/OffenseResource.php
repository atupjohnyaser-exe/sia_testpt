<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OffenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'subject_id' => $this->subject_id, 'subject' => $this->whenLoaded('subject', fn () => ['id' => $this->subject->id, 'case_id' => $this->subject->case_id, 'name' => $this->subject->name, 'alias' => $this->subject->alias]), 'category' => $this->category, 'charge' => $this->charge, 'occurred_on' => $this->occurred_on?->toDateString(), 'location' => $this->location, 'disposition' => $this->disposition, 'status_effect' => $this->status_effect, 'severity' => $this->severity];
    }
}
