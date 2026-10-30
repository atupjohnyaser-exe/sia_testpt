<?php

namespace App\Models;

use App\Enums\AuditAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append only. The model refuses updates and deletes.
 */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'subject_id', 'action', 'meta', 'ip_address', 'created_at'];

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class)->withTrashed();
    }
}
