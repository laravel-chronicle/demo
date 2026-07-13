<?php

use App\Models\Patient;
use Chronicle\Encryption\SubjectKeyManager;
use Database\Seeders\ClinicianSeeder;
use Illuminate\Support\Facades\Schema;

it('creates the subject-key and legal-hold lifecycle tables', function () {
    expect(Schema::hasTable('chronicle_subject_keys'))->toBeTrue()
        ->and(Schema::hasTable('chronicle_legal_holds'))->toBeTrue();
});

it('keys a patient subject when encryption is enabled', function () {
    enableDemoEncryption();
    $this->seed(ClinicianSeeder::class);

    $patient = Patient::factory()->create();

    $state = app(SubjectKeyManager::class)
        ->stateFor(Patient::class, (string) $patient->getKey());

    expect($state->erased)->toBeFalse()
        ->and($state->dek)->not->toBeNull();
});
