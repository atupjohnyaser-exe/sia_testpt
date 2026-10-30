<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfficerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'badge_id' => $this->badge_id,
            'initials' => $this->initials,
            'rank' => $this->rank,
            'clearance_level' => $this->clearance_level,
            'clearance_label' => $this->clearance()->label(),
        ];
    }
}
