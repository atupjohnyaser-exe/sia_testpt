<?php

namespace App\Http\Resources;

use App\Enums\Clearance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class SubjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canSeeAdvisory = $request->user()?->hasClearance(Clearance::Officer) ?? false;

        return [
            'id' => $this->id,
            'case_id' => $this->case_id,
            'name' => $this->name,
            'alias' => $this->alias,
            'age' => $this->age,
            'status' => $this->status->value,
            'bounty' => $this->bounty,
            'cost_to_state' => $this->cost_to_state,
            'fines_due' => $this->fines_due,
            'fines_paid' => $this->fines_paid,
            'total_infractions' => $this->total_infractions,
            'unserved_infractions' => $this->unserved_infractions,
            'times_evaded' => $this->times_evaded,
            'times_apprehended' => $this->times_apprehended,
            'threat' => [
                'level' => $this->threat_level,
                'label' => $this->threat()->label(),
                'response' => $this->threat()->response(),
                'overridden' => $this->threat_override !== null,
                'override_reason' => $this->threat_override_reason,
            ],
            'last_known_location' => $this->last_known_location,
            'height' => $this->height,
            'eye_color' => $this->eye_color,
            'hair_color' => $this->hair_color,
            'distinguishing_features' => $this->distinguishing_features,
            'photo_url' => $this->displayPhotoUrl(),
            'photo_source' => $this->photo_path ? 'upload' : ($this->photo_url ? 'url' : null),
            'officer_advisory' => $canSeeAdvisory ? $this->officer_advisory : 'RESTRICTED',
            'advisory_reviewed_at' => $this->advisory_reviewed_at,
            'advisory_reviewed_by' => $this->whenLoaded('advisoryReviewer', fn () => $this->advisoryReviewer?->name),
            'recent_infractions' => InfractionResource::collection($this->whenLoaded('infractions')),
            'archived_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * An uploaded portrait is shown through a signed link that stops working after the session limit.
     * A pasted outside link is shown as it is.
     */
    private function displayPhotoUrl(): ?string
    {
        if ($this->photo_path) {
            return URL::temporarySignedRoute(
                'subjects.photo',
                now()->addMinutes((int) config('rockport.token_ttl_minutes')),
                ['subject' => $this->id]
            );
        }

        return $this->photo_url;
    }
}
