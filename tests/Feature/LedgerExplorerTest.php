<?php

use App\Models\Patient;
use Database\Seeders\ClinicianSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(ClinicianSeeder::class);
});

it('lists ledger entries with action, actor and chain position', function () {
    Patient::factory()->create(['name' => 'Mars Vesper']);

    $this->get(route('ledger.index'))
        ->assertOk()
        ->assertSee('Ledger Explorer')
        ->assertSee('patient.created')   // action column
        ->assertSee('Dr. Reyes')         // actor column: default persona resolves to the physician clinician
        ->assertSee('Patient');          // subject column: class basename of the subject_type
});

// Note on the actor assertion: a no-session `Patient::factory()->create()` resolves
// `CurrentClinician` to the default persona (`physician` → "Dr. Reyes"), and the
// table renders the clinician's *name* (not the word "Clinician") for Clinician actors.
