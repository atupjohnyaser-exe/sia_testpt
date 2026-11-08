<?php

namespace App\Http\Resources;

use App\Enums\Clearance;
use App\Support\Ranks;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccessRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cap = min(Ranks::cap((string) $this->rank), 4);

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'full_name' => $this->full_name,
            'rank' => $this->rank,
            'requested_level' => $this->requested_level,
            'requested_label' => Clearance::tryFrom($this->requested_level)?->label(),
            'auto_level' => $this->auto_level,
            'max_level' => $cap,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'phone' => $this->phone,
            'email' => $this->email,
            'station' => $this->station,
            'address' => implode(', ', array_filter([
                $this->address_street,
                $this->address_barangay ? 'Brgy. '.$this->address_barangay : null,
                $this->address_municipality,
            ])),
            'years_of_service' => $this->years_of_service,
            'justification' => $this->justification,
            'reviewed_by' => $this->whenLoaded('reviewer', fn () => $this->reviewer?->name),
            'reviewed_at' => $this->reviewed_at,
            'review_note' => $this->review_note,
            'approved_level' => $this->approved_level,
            'badge_id' => $this->whenLoaded('user', fn () => $this->user?->badge_id),
            'created_at' => $this->created_at,
        ];
    }
}
