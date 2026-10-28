<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'case_id', 'name', 'alias', 'age', 'status', 'classification', 'last_known_location',
        'height', 'eye_color', 'hair_color', 'distinguishing_features', 'photo_url', 'risk_score',
        'risk_override', 'officer_advisory', 'advisory_reviewed_by', 'advisory_reviewed_at',
    ];

    protected function casts(): array
    {
        return ['risk_score' => 'integer', 'risk_override' => 'boolean', 'advisory_reviewed_at' => 'datetime'];
    }

    public function offenses() { return $this->hasMany(Offense::class); }
    public function notes() { return $this->hasMany(CaseNote::class); }
    public function auditLogs() { return $this->hasMany(AuditLog::class); }
    public function advisoryReviewer() { return $this->belongsTo(User::class, 'advisory_reviewed_by'); }
}
