<?php

use App\Models\Patient;
use Database\Seeders\ClinicianSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(ClinicianSeeder::class);
});

it('lists patients with a link to each detail page', function () {
    $patient = Patient::factory()->create(['name' => 'Mars Vesper']);

    $this->get(route('patients.index'))
        ->assertOk()
        ->assertSee('Mars Vesper')
        ->assertSee(route('patients.show', $patient));
});
