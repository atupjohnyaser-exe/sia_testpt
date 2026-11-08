<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessRequest extends Model
{
    public const PENDING = 'PENDING';
    public const APPROVED = 'APPROVED';
    public const REJECTED = 'REJECTED';

    protected $fillable = [
        'reference', 'full_name', 'rank', 'requested_level', 'auto_level', 'date_of_birth', 'phone', 'email',
        'station', 'address_municipality', 'address_barangay', 'address_street', 'years_of_service',
        'justification', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'auto_level' => 'boolean',
            'requested_level' => 'integer',
            'approved_level' => 'integer',
            'years_of_service' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }
}
