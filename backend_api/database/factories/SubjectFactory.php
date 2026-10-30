<?php

namespace Database\Factories;

use App\Enums\SubjectStatus;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return [
            'case_id' => 'RP-'.fake()->unique()->numerify('####').'-'.Str::upper(Str::random(2)),
            'name' => fake()->name(),
            'alias' => fake()->word(),
            'age' => fake()->numberBetween(18, 65),
            'last_known_location' => 'Civic District',
            'status' => SubjectStatus::Wanted,
        ];
    }

    public function status(SubjectStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
