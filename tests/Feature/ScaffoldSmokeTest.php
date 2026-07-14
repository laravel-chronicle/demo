<?php

use App\Models\Patient;
use Database\Seeders\ClinicianSeeder;

beforeEach(fn () => $this->withoutVite());

it('boots and renders every public screen without error', function () {
    $routes = ['home', 'patients.index', 'ledger.index', 'lab.index', 'how.it.works'];

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

it('renders the integrity lab with all five panels', function () {
    $this->withoutVite();
    // Anchoring is enabled in the app's .env; force the TSA off here so the
    // full-compromise panel deterministically shows its "not configured"
    // placeholder regardless of the machine's environment.
    config(['chronicle.anchoring.providers.rfc3161.tsa_url' => null]);
    $this->seed(ClinicianSeeder::class);
    Patient::factory()->count(2)->create();

    $this->get(route('lab.index'))
        ->assertOk()
        ->assertSee('Integrity Lab')
        ->assertSee('Tampering simulator')   // 4a
        ->assertSee('Scrub it')              // 4a control
        ->assertSee('Full lifecycle')        // 4b
        ->assertSee('Generate activity')     // 4b control
        ->assertSee('Auditor view')          // 4c
        ->assertSee('Generate signed report')// 4c control
        ->assertSee('Key rotation')          // 4d
        ->assertSee('Rotate to chronicle-key-2') // 4d control
        ->assertSee('Full-compromise demo')  // 4e heading
        ->assertSee('Configure a TSA to see this demo'); // 4e honest placeholder (no TSA in test env)
});

it('shows the manual Reset demo control in the banner', function () {
    $this->withoutVite();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Public demo - all data is fictional - resets hourly.')
        ->assertSee('Reset demo');
});
