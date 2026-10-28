<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $restricted = (int) $request->user()?->clearance_level < 3;
        return [
            'id' => $this->id, 'case_id' => $this->case_id, 'name' => $this->name, 'alias' => $this->alias,
            'age' => $this->age, 'status' => $this->status, 'classification' => $this->classification,
            'last_known_location' => $this->last_known_location, 'height' => $this->height, 'eye_color' => $this->eye_color,
            'hair_color' => $this->hair_color, 'distinguishing_features' => $this->distinguishing_features,
            'photo_url' => $this->photo_url, 'risk_score' => $this->risk_score,
            'officer_advisory' => $restricted ? 'RESTRICTED' : $this->officer_advisory,
            'advisory_reviewed_at' => $this->advisory_reviewed_at,
            'advisory_reviewed_by' => $this->whenLoaded('advisoryReviewer', fn () => $this->advisoryReviewer?->name),
            'offenses_count' => $this->whenCounted('offenses'),
            'offenses' => OffenseResource::collection($this->whenLoaded('offenses')),
            'notes' => NoteResource::collection($this->whenLoaded('notes')),
            'created_at' => $this->created_at,
        ];
    }
}
