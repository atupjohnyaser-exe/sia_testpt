<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseNote extends Model
{
    protected $fillable = ['subject_id', 'user_id', 'body', 'access_level'];

    protected function casts(): array
    {
        return ['access_level' => 'integer'];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
