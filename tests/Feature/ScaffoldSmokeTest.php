<?php

use App\Models\Patient;
use Database\Seeders\ClinicianSeeder;

beforeEach(fn () => $this->withoutVite());

it('boots and renders every public screen without error', function () {
    $routes = ['home', 'patients.index', 'ledger.index', 'lab.index', 'auditors.index', 'how.it.works'];

    foreach ($routes as $name) {
        $this->get(route($name))
            ->assertOk()
            ->assertSee('all data is fictional')
            ->assertSee('MedLedger');
    }
});

it('exposes a health check', function () {
    $this->get('/up')->assertOk();
});

it('renders a seeded patient detail with its audit trail', function () {
    $this->seed(ClinicianSeeder::class);
    $patient = Patient::factory()->create(['name' => 'Saturn Vesper']);

    $this->get(route('patients.show', $patient))
        ->assertOk()
        ->assertSee('Saturn Vesper')
        ->assertSee('Audit trail')
        ->assertSee('patient.viewed');
});

it('renders the ledger explorer with seeded entries', function () {
    $this->withoutVite();
    $this->seed(ClinicianSeeder::class);
    Patient::factory()->create(['name' => 'Saturn Vesper']);

    $this->get(route('ledger.index'))
        ->assertOk()
        ->assertSee('Ledger Explorer')
        ->assertSee('patient.created')
        ->assertSee('Verify integrity');
});
