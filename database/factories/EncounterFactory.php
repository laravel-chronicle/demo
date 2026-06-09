<?php

namespace Database\Factories;

use App\Models\Clinician;
use App\Models\Encounter;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Encounter>
 */
class EncounterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'clinician_id' => Clinician::factory(),
            'reason' => fake()->randomElement(['Annual check-up', 'Follow-up', 'Acute visit']),
            'vitals' => ['bp' => '120/80', 'hr' => fake()->numberBetween(60, 100)],
            'notes' => fake()->sentence(),
            'occurred_at' => fake()->dateTimeBetween('-1 year'),
        ];
    }
}
