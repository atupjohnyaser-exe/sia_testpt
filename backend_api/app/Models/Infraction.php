<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Infraction extends Model
{
    protected $fillable = [
        'subject_id', 'infraction_type_id', 'manhunt_id', 'occurred_on', 'location',
        'disposition', 'fine_amount', 'bounty_value', 'reported_by',
    ];

    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'fine_amount' => 'integer',
            'bounty_value' => 'integer',
            'served_at' => 'datetime',
            'fine_paid_at' => 'datetime',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(InfractionType::class, 'infraction_type_id');
    }

    public function manhunt(): BelongsTo
    {
        return $this->belongsTo(Manhunt::class);
    }

    public function scopeUnserved(Builder $query): Builder
    {
        return $query->whereNull('served_at');
    }
}
