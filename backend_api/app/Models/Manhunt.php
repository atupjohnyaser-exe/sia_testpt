<?php

namespace App\Models;

use App\Enums\ManhuntOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manhunt extends Model
{
    protected $fillable = [
        'code', 'subject_id', 'started_at', 'duration_seconds', 'outcome', 'district',
        'officers_involved', 'officers_injured', 'units_stood_down', 'checkpoints', 'k9_units',
        'helicopters', 'property_damage_incidents', 'insurance_claims',
        'cost_to_state', 'bounty_value', 'cost_breakdown', 'logged_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'duration_seconds' => 'integer',
            'outcome' => ManhuntOutcome::class,
            'cost_to_state' => 'integer',
            'bounty_value' => 'integer',
            'cost_breakdown' => 'array',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function infractions(): HasMany
    {
        return $this->hasMany(Infraction::class);
    }

    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
