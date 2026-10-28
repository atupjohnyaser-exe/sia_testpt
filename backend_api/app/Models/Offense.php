<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Offense extends Model
{
    use HasFactory;

    protected $fillable = ['subject_id', 'category', 'charge', 'occurred_on', 'location', 'disposition', 'status_effect', 'severity'];
    protected function casts(): array { return ['occurred_on' => 'date']; }
    public function subject() { return $this->belongsTo(Subject::class); }
}
