<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManhuntResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'subject' => $this->whenLoaded('subject', fn () => $this->subject ? [
                'id' => $this->subject->id,
                'case_id' => $this->subject->case_id,
                'name' => $this->subject->name,
                'alias' => $this->subject->alias,
            ] : null),
            'started_at' => $this->started_at,
            'duration_seconds' => $this->duration_seconds,
            'duration_label' => sprintf('%02d:%02d', intdiv($this->duration_seconds, 60), $this->duration_seconds % 60),
            'outcome' => $this->outcome->value,
            'district' => $this->district,
            'officers_involved' => $this->officers_involved,
            'officers_injured' => $this->officers_injured,
            'units_stood_down' => $this->units_stood_down,
            'checkpoints' => $this->checkpoints,
            'k9_units' => $this->k9_units,
            'helicopters' => $this->helicopters,
            'property_damage_incidents' => $this->property_damage_incidents,
            'insurance_claims' => $this->insurance_claims,
            'cost_to_state' => $this->cost_to_state,
            'bounty_value' => $this->bounty_value,
            'cost_breakdown' => $this->cost_breakdown ?? [],
            'infractions_count' => $this->whenCounted('infractions'),
            'infractions' => InfractionResource::collection($this->whenLoaded('infractions')),
            'notes' => $this->notes,
        ];
    }
}
