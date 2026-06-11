<?php

use App\Livewire\Patients\Show;
use App\Models\Patient;
use Chronicle\Facades\Chronicle;
use Database\Seeders\ClinicianSeeder;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(ClinicianSeeder::class));

it('audits an allergy edit with a diff (auto patient.updated)', function () {
    $patient = Patient::factory()->create(['allergies' => 'None']);

    Livewire::test(Show::class, ['patient' => $patient])
        ->set('allergies', 'Penicillin')
        ->call('saveAllergies')
        ->assertHasNoErrors();

    $entry = Chronicle::query()->forSubject($patient)->action('patient.updated')->latest()->first();

    expect($entry->diff['allergies']['old'])->toBe('None')
        ->and($entry->diff['allergies']['new'])->toBe('Penicillin');
});

it('audits a new prescription (auto prescription.created)', function () {
    $patient = Patient::factory()->create();

    Livewire::test(Show::class, ['patient' => $patient])
        ->set('drug', 'Amoxicillin')
        ->set('dose', '500mg')
        ->call('prescribe')
        ->assertHasNoErrors();

    $rx = $patient->prescriptions()->sole();

    expect($rx->drug)->toBe('Amoxicillin')
        ->and(Chronicle::query()->forSubject($rx)->action('prescription.created')->exists())->toBeTrue();
});

it('audits an amendment with a before/after diff and reason', function () {
    $patient = Patient::factory()->create(['notes' => 'Original note.']);

    Livewire::test(Show::class, ['patient' => $patient])
        ->set('amendment', 'Corrected note.')
        ->set('amendmentReason', 'Fixed a transcription error')
        ->call('amend')
        ->assertHasNoErrors();

    $entry = Chronicle::query()->forSubject($patient)->action('patient.amended')->first();

    expect($entry->diff['notes']['old'])->toBe('Original note.')
        ->and($entry->diff['notes']['new'])->toBe('Corrected note.')
        ->and($entry->context['reason'])->toBe('Fixed a transcription error')
        ->and($patient->refresh()->notes)->toBe('Corrected note.');
});

it('does not also record a generic patient.updated when amending', function () {
    $patient = Patient::factory()->create(['notes' => 'Original note.']);

    Livewire::test(Show::class, ['patient' => $patient])
        ->set('amendment', 'Corrected note.')
        ->set('amendmentReason', 'Fixed a transcription error')
        ->call('amend');

    expect(Chronicle::query()->forSubject($patient)->action('patient.updated')->exists())->toBeFalse();
});
