<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SightingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'district' => $this->district,
            'address' => $this->address,
            'seen_at' => $this->seen_at,
            'source' => $this->source,
            'notes' => $this->notes,
        ];
    }
}
