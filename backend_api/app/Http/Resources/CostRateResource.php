<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CostRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'label' => $this->label,
            'unit_cost' => $this->unit_cost,
        ];
    }
}
