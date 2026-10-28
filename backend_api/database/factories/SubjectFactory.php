<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Subject> */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        $name = fake()->name();
        return [
            'case_id' => 'RP-'.fake()->unique()->numerify('####').'-'.Str::upper(Str::random(2)),
            'name' => $name, 'alias' => fake()->word(), 'age' => fake()->numberBetween(18, 65),
            'status' => fake()->randomElement(['WANTED', 'IN_CUSTODY', 'PAROLED', 'CLEARED']),
            'classification' => 'Under review', 'last_known_location' => fake()->city(), 'risk_score' => fake()->numberBetween(15, 99),
        ];
    }
}
