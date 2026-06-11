<?php

use Database\Seeders\ClinicianSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(ClinicianSeeder::class);
});

it('renders the integrity lab with all four panel sections', function () {
    $this->get(route('lab.index'))
        ->assertOk()
        ->assertSee('Integrity Lab')
        ->assertSee('Tampering simulator')
        ->assertSee('Full lifecycle')
        ->assertSee('Auditor view')
        ->assertSee('Key rotation');
});
