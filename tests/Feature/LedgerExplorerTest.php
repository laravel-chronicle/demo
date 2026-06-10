<?php

use App\Livewire\Ledger\Index;
use App\Models\Patient;
use Database\Seeders\ClinicianSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

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

it('reports a valid ledger when Verify is pressed', function () {
    Patient::factory()->count(2)->create();

    Livewire::test(Index::class)
        ->call('verify')
        ->assertSet('verified', true)
        ->assertSet('valid', true)
        ->assertSee('Ledger verified');
});

it('shows a broken-at-entry failure with a reason when an entry is tampered', function () {
    $patient = Patient::factory()->create();

    $entry = DB::table('chronicle_entries')->orderBy('sequence')->first();
    DB::table('chronicle_entries')
        ->where('id', $entry->id)
        ->update(['payload' => json_encode(['tampered' => true])]);

    Livewire::test(Index::class)
        ->call('verify')
        ->assertSet('verified', true)
        ->assertSet('valid', false)
        ->assertSet('failedEntryId', $entry->id)
        ->assertSee('Verification failed')
        ->assertSee('payload');
});
