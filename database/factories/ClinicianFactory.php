<?php

namespace Database\Factories;

use App\Models\Clinician;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Clinician>
 */
class ClinicianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Dr. '.fake()->unique()->lastName(),
            'role' => fake()->randomElement(['physician', 'nurse', 'admin']),
            'persona_key' => null,
        ];
    }
}
