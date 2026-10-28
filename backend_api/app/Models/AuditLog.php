<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id', 'subject_id', 'action', 'meta', 'created_at'];
    protected function casts(): array { return ['meta' => 'array', 'created_at' => 'datetime']; }
    public function user() { return $this->belongsTo(User::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
}
