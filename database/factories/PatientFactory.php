<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $planet = fake()->randomElement([
            'Mercury', 'Venus', 'Mars', 'Jupiter', 'Saturn', 'Neptune', 'Pluto', 'Titan', 'Europa', 'Io',
        ]);

        return [
            'name' => $planet.' '.fake()->lastName(),
            'dob' => fake()->dateTimeBetween('-90 years', '-1 year')->format('Y-m-d'),
            'mrn' => 'MRN-'.fake()->unique()->numerify('######'),
            'allergies' => fake()->randomElement(['None', 'Penicillin', 'Latex', 'Peanuts']),
            'notes' => fake()->sentence(),
        ];
    }
}
