<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseNote extends Model
{
    protected $fillable = ['subject_id', 'user_id', 'body', 'access_level'];
    public function subject() { return $this->belongsTo(Subject::class); }
    public function user() { return $this->belongsTo(User::class); }
}
