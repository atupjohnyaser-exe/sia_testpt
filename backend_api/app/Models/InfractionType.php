<?php

namespace App\Models;

use App\Enums\Severity;
use Illuminate\Database\Eloquent\Model;

class InfractionType extends Model
{
    protected $fillable = ['code', 'name', 'severity', 'fine_amount', 'bounty_value', 'is_active'];

    protected function casts(): array
    {
        return [
            'severity' => Severity::class,
            'fine_amount' => 'integer',
            'bounty_value' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
