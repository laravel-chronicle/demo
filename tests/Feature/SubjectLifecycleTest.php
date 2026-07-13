<?php

use App\Models\Patient;
use App\Support\LedgerVerifier;
use Chronicle\Encryption\SubjectKeyManager;
use Chronicle\Facades\Chronicle;
use Chronicle\Lifecycle\LegalHold;
use Database\Seeders\ClinicianSeeder;
use Database\Seeders\PatientSeeder;
use Database\Seeders\SubjectLifecycleSeeder;

beforeEach(function () {
    enableDemoEncryption();
    pinSigningKey();
});

it('erases one patient, holds another, and leaves the rest encrypted', function () {
    $this->seed([ClinicianSeeder::class, PatientSeeder::class, SubjectLifecycleSeeder::class]);

    $erased = Patient::query()->where('name', 'Neptune Vesper')->sole();
    $held = Patient::query()->where('name', 'Saturn Vesper')->sole();
    $encrypted = Patient::query()->where('name', 'Mars Vesper')->sole();

    $keys = app(SubjectKeyManager::class);

    expect($keys->stateFor(Patient::class, (string) $erased->getKey())->erased)->toBeTrue()
        ->and(LegalHold::isHeld(Patient::class, (string) $held->getKey()))->toBeTrue()
        ->and($keys->stateFor(Patient::class, (string) $encrypted->getKey())->erased)->toBeFalse();
});

it('appends a subject.erased proof for the erased patient', function () {
    $this->seed([ClinicianSeeder::class, PatientSeeder::class, SubjectLifecycleSeeder::class]);

    $erased = Patient::query()->where('name', 'Neptune Vesper')->sole();

    expect(
        Chronicle::query()->forSubject($erased)->action('subject.erased')->exists()
    )->toBeTrue();
});

it('still verifies the chain after erasure', function () {
    $this->seed([ClinicianSeeder::class, PatientSeeder::class, SubjectLifecycleSeeder::class]);

    expect(app(LedgerVerifier::class)->run()->valid)->toBeTrue();
});
