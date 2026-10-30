<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'badge_id' => fake()->unique()->bothify('RP-####'),
            'initials' => 'TS',
            'rank' => 'Detective',
            'clearance_level' => 3,
            'is_active' => true,
            'password' => Hash::make('password'),
        ];
    }

    public function clearance(int $level): static
    {
        return $this->state(fn () => ['clearance_level' => $level]);
    }
}
