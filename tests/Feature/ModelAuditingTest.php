<?php

use App\Models\Clinician;
use App\Models\Patient;
use Chronicle\Facades\Chronicle;
use Database\Seeders\ClinicianSeeder;
use Database\Seeders\PatientSeeder;

beforeEach(fn () => $this->seed(ClinicianSeeder::class));

it('records patient.created automatically with the active clinician as actor', function () {
    session()->put('demo_persona', 'nurse');
    $nurse = Clinician::query()->where('persona_key', 'nurse')->sole();

    $patient = Patient::factory()->create();

    $entry = Chronicle::query()->forSubject($patient)->action('patient.created')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->actor_type)->toBe(Clinician::class)
        ->and($entry->actor_id)->toBe((string) $nurse->getKey());
});

it('records patient.updated with a diff of the changed fields', function () {
    $patient = Patient::factory()->create(['allergies' => 'None']);

    $patient->update(['allergies' => 'Penicillin']);

    $entry = Chronicle::query()->forSubject($patient)->action('patient.updated')->latest()->first();

    expect($entry->diff)->toHaveKey('allergies')
        ->and($entry->diff['allergies']['old'])->toBe('None')
        ->and($entry->diff['allergies']['new'])->toBe('Penicillin');
});

it('records prescription.created when a prescription is added to a patient', function () {
    $patient = Patient::factory()->create();
    $physicianId = Clinician::query()->where('persona_key', 'physician')->value('id');

    $rx = $patient->prescriptions()->create([
        'clinician_id' => $physicianId,
        'drug' => 'Amoxicillin',
        'dose' => '500mg',
        'status' => 'active',
    ]);

    expect(Chronicle::query()->forSubject($rx)->action('prescription.created')->exists())->toBeTrue();
});

it('records prescription.deleted when a prescription is removed', function () {
    $patient = Patient::factory()->create();
    $rx = $patient->prescriptions()->create([
        'clinician_id' => Clinician::query()->where('persona_key', 'physician')->value('id'),
        'drug' => 'Lisinopril',
        'dose' => '10mg',
        'status' => 'active',
    ]);

    $rx->delete();

    expect(Chronicle::query()->forSubject($rx)->action('prescription.deleted')->exists())->toBeTrue();
});

it('seeds a populated, queryable ledger', function () {
    $this->seed(PatientSeeder::class);

    expect(Chronicle::query()->actionPrefix('patient.')->count())->toBeGreaterThan(0)
        ->and(Patient::query()->count())->toBeGreaterThan(0);
});
