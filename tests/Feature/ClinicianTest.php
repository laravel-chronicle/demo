<?php

use App\Models\Clinician;
use App\Support\CurrentClinician;
use Database\Seeders\ClinicianSeeder;

it('seeds one clinician per demo persona', function () {
    $this->seed(ClinicianSeeder::class);

    expect(Clinician::query()->count())->toBe(3)
        ->and(Clinician::query()->where('persona_key', 'physician')->value('name'))->toBe('Dr. Reyes')
        ->and(Clinician::query()->where('persona_key', 'nurse')->value('role'))->toBe('nurse');
});

it('resolves the current clinician from the active persona', function () {
    $this->seed(ClinicianSeeder::class);

    session()->put('demo_persona', 'nurse');

    expect(app(CurrentClinician::class)->get()->name)->toBe('Nurse Okoro');
});

it('falls back to the default persona when none is set or the key is unknown', function () {
    $this->seed(ClinicianSeeder::class);

    session()->put('demo_persona', 'intruder');

    expect(app(CurrentClinician::class)->get()->role)->toBe('physician');
});
