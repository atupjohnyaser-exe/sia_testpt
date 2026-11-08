<?php

namespace App\Http\Resources;

use App\Enums\Clearance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isOwner = $user !== null && $this->filed_by === $user->id;

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'type' => $this->whenLoaded('type', fn () => $this->type ? [
                'id' => $this->type->id,
                'code' => $this->type->code,
                'name' => $this->type->name,
                'severity' => $this->type->severity->value,
            ] : null),
            'occurred_at' => $this->occurred_at,
            'municipality' => $this->municipality,
            'barangay' => $this->barangay,
            'street' => $this->street,
            'person_name' => $this->person_name,
            'person_description' => $this->person_description,
            'narrative' => $this->narrative,
            'filed_by' => $this->whenLoaded('filer', fn () => $this->filer?->only(['id', 'name', 'badge_id', 'rank'])),
            'reviewed_by' => $this->whenLoaded('reviewer', fn () => $this->reviewer?->name),
            'reviewed_at' => $this->reviewed_at,
            'created_at' => $this->created_at,
            // Patrol officers can edit their own report until it is reviewed. Senior officers and above can edit any.
            'can_edit' => $user !== null && ($user->hasClearance(Clearance::Senior) || ($isOwner && $this->status === 'FILED')),
        ];
    }
}
