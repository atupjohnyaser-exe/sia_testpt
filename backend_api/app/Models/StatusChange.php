<?php

namespace App\Models;

use App\Enums\SubjectStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusChange extends Model
{
    public $timestamps = false;

    protected $fillable = ['subject_id', 'from_status', 'to_status', 'reason', 'changed_by', 'created_at'];

    protected function casts(): array
    {
        return [
            'from_status' => SubjectStatus::class,
            'to_status' => SubjectStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
