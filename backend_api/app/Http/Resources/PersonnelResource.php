<?php

namespace App\Http\Resources;

use App\Enums\Clearance;
use App\Support\Ranks;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonnelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'badge_id' => $this->badge_id,
            'name' => $this->name,
            'initials' => $this->initials,
            'rank' => $this->rank,
            'rank_cap' => min(Ranks::cap((string) $this->rank), 5),
            'clearance_level' => $this->clearance_level,
            'clearance_label' => $this->clearance()->label(),
            'is_active' => $this->is_active,
            // Chief Admin accounts are changed from the command line only.
            'protected' => $this->hasClearance(Clearance::Chief),
            'station' => $this->station,
            'phone' => $this->phone,
            'email' => $this->email,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'address' => $this->address,
            'years_of_service' => $this->years_of_service,
            'last_login_at' => $this->last_login_at,
            'approved_at' => $this->approved_at,
            'approved_by' => $this->whenLoaded('approver', fn () => $this->approver?->name),
            'password_issued_at' => $this->password_issued_at,
            'created_at' => $this->created_at,
        ];
    }
}
