<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bulletin extends Model
{
    protected $fillable = ['body', 'ref', 'published_at', 'is_active'];
    protected function casts(): array { return ['published_at' => 'datetime', 'is_active' => 'boolean']; }
}
