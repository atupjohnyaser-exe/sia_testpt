<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InfractionTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'severity' => $this->severity->value,
            'fine_amount' => $this->fine_amount,
            'bounty_value' => $this->bounty_value,
        ];
    }
}
