<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'badge_id', 'initials', 'rank', 'clearance_level', 'password'];
    protected $hidden = ['password', 'remember_token'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clearance_level' => 'integer',
            'password' => 'hashed',
        ];
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function notes()
    {
        return $this->hasMany(CaseNote::class);
    }
}
