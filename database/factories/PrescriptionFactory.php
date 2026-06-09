<?php

namespace Database\Factories;

use App\Models\Clinician;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
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
            'drug' => fake()->randomElement(['Amoxicillin', 'Lisinopril', 'Metformin', 'Atorvastatin']),
            'dose' => fake()->randomElement(['250mg', '500mg', '10mg', '20mg']),
            'status' => 'active',
        ];
    }
}
