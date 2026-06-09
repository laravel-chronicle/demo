<?php

namespace Database\Seeders;

use App\Models\Clinician;
use App\Models\Patient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PatientSeeder extends Seeder
{
    /**
     * Deterministic synthetic clinic data. Creating each model fires
     * Chronicle's automatic auditing, so the ledger has substance immediately.
     */
    public function run(): void
    {
        $physician = Clinician::query()->where('persona_key', 'physician')->firstOrFail();
        $nurse = Clinician::query()->where('persona_key', 'nurse')->firstOrFail();

        $planets = ['Mercury', 'Venus', 'Mars', 'Jupiter', 'Saturn', 'Neptune'];

        foreach ($planets as $index => $planet) {
            $patient = Patient::query()->create([
                'name' => $planet.' Vesper',
                'dob' => Carbon::create(1960 + $index, 1 + $index, 5 + $index)->toDateString(),
                'mrn' => sprintf('MRN-%06d', 100000 + $index),
                'allergies' => ['None', 'Penicillin', 'Latex'][$index % 3],
                'notes' => 'Synthetic patient record for the MedLedger demo.',
            ]);

            $patient->encounters()->create([
                'clinician_id' => $physician->getKey(),
                'reason' => 'Annual check-up',
                'vitals' => ['bp' => '120/80', 'hr' => 72],
                'notes' => 'Routine visit.',
                'occurred_at' => Carbon::create(2026, 1, 10 + $index, 9),
            ]);

            $patient->prescriptions()->create([
                'clinician_id' => $nurse->getKey(),
                'drug' => 'Lisinopril',
                'dose' => '10mg',
                'status' => 'active',
            ]);
        }
    }
}
