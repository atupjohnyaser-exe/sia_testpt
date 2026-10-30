<?php

namespace App\Models;

use App\Enums\SubjectStatus;
use App\Enums\ThreatLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Only profile fields can be mass assigned. Status, bounty, threat level and the
     * other numbers are set by the services, never straight from a request.
     */
    protected $fillable = [
        'case_id', 'name', 'alias', 'age', 'last_known_location', 'height', 'eye_color',
        'hair_color', 'distinguishing_features', 'photo_url', 'officer_advisory',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubjectStatus::class,
            'threat_level' => 'integer',
            'threat_override' => 'integer',
            'bounty' => 'integer',
            'cost_to_state' => 'integer',
            'fines_due' => 'integer',
            'fines_paid' => 'integer',
            'total_infractions' => 'integer',
            'unserved_infractions' => 'integer',
            'times_evaded' => 'integer',
            'times_apprehended' => 'integer',
            'advisory_reviewed_at' => 'datetime',
        ];
    }

    public function infractions(): HasMany
    {
        return $this->hasMany(Infraction::class);
    }

    public function manhunts(): HasMany
    {
        return $this->hasMany(Manhunt::class);
    }

    public function sightings(): HasMany
    {
        return $this->hasMany(Sighting::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CaseNote::class);
    }

    public function statusChanges(): HasMany
    {
        return $this->hasMany(StatusChange::class);
    }

    public function advisoryReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advisory_reviewed_by');
    }

    public function threat(): ThreatLevel
    {
        return ThreatLevel::tryFrom((int) $this->threat_level) ?? ThreatLevel::Routine;
    }

    /** Everyone who is not cleared. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', SubjectStatus::Cleared->value);
    }
}
