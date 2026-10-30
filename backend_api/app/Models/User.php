<?php

namespace App\Models;

use App\Enums\Clearance;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory;

    protected $fillable = ['name', 'badge_id', 'initials', 'rank', 'clearance_level', 'is_active', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'clearance_level' => 'integer',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function clearance(): Clearance
    {
        return Clearance::tryFrom((int) $this->clearance_level) ?? Clearance::Clerk;
    }

    public function hasClearance(Clearance|int $minimum): bool
    {
        $level = $minimum instanceof Clearance ? $minimum->value : $minimum;

        return (int) $this->clearance_level >= $level;
    }
}
