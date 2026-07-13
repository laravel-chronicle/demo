<?php

use App\Livewire\Patients\Show;
use App\Models\Clinician;
use App\Models\Patient;
use Chronicle\Encryption\EntryDecryptor;
use Chronicle\Facades\Chronicle;
use Database\Seeders\ClinicianSeeder;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(ClinicianSeeder::class));

it('records an access event when the patient detail is mounted', function () {
    $patient = Patient::factory()->create();

    Livewire::test(Show::class, ['patient' => $patient]);

    $entry = Chronicle::query()->forSubject($patient)->action('patient.viewed')->first();

    $context = app(EntryDecryptor::class)->field($entry, 'context');

    expect($entry)->not->toBeNull()
        ->and($entry->actor_type)->toBe(Clinician::class)
        ->and($context['reason'])->toBe('Opened patient detail');
});

it('shows the access event in the patient audit trail', function () {
    $this->withoutVite();
    $patient = Patient::factory()->create(['name' => 'Jupiter Vesper']);

    $this->get(route('patients.show', $patient))
        ->assertOk()
        ->assertSee('Jupiter Vesper')
        ->assertSee('patient.viewed');
});
