<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    public const FILED = 'FILED';
    public const REVIEWED = 'REVIEWED';

    protected $fillable = [
        'reference', 'filed_by', 'infraction_type_id', 'occurred_at', 'municipality', 'barangay', 'street',
        'person_name', 'person_description', 'narrative',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function filer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filed_by');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(InfractionType::class, 'infraction_type_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
