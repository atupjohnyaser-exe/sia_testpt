<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostRate extends Model
{
    protected $fillable = ['code', 'label', 'unit_cost', 'updated_by'];

    protected function casts(): array
    {
        return ['unit_cost' => 'integer'];
    }
}
