<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sighting extends Model
{
    protected $fillable = ['subject_id', 'district', 'address', 'seen_at', 'source', 'notes', 'reported_by'];

    protected function casts(): array
    {
        return ['seen_at' => 'datetime'];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
