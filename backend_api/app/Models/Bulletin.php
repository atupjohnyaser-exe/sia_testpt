<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bulletin extends Model
{
    protected $fillable = ['ref', 'body', 'is_active', 'published_at'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
