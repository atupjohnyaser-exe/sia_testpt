<?php

namespace Database\Factories;

use App\Models\Offense;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Offense> */
class OffenseFactory extends Factory
{
    protected $model = Offense::class;
    public function definition(): array
    {
        return ['subject_id' => Subject::factory(), 'category' => 'other-crime', 'charge' => 'Other Crime', 'occurred_on' => fake()->date(), 'location' => fake()->city(), 'disposition' => 'Under investigation', 'status_effect' => 'WANTED', 'severity' => 'LOW'];
    }
}
