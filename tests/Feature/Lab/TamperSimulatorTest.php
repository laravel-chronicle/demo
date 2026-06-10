<?php

use App\Livewire\Lab\TamperSimulator;
use App\Models\Patient;
use App\Support\LedgerVerifier;
use Chronicle\Verification\VerificationFailure;
use Database\Seeders\ClinicianSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(ClinicianSeeder::class);
    Patient::factory()->count(3)->create();
});

it('starts from a valid baseline', function () {
    Livewire::test(TamperSimulator::class)
        ->assertSet('baselineValid', true)
        ->assertSet('tampered', false)
        ->assertSee('chain intact');
});

it('breaks the chain with a scrub (raw delete) and localizes the failure', function () {
    $middle = DB::table('chronicle_entries')->orderBy('sequence')->skip(2)->first();
    $successor = DB::table('chronicle_entries')
        ->where('sequence', '>', $middle->sequence)->orderBy('sequence')->first();

    Livewire::test(TamperSimulator::class)
        ->call('selectEntry', $middle->id)
        ->call('scrub')
        ->assertSet('tampered', true)
        ->assertSet('valid', false)
        ->assertSet('failureType', VerificationFailure::ChainHashMismatch->value)
        ->assertSet('failedEntryId', $successor->id)
        ->assertSee('Verification failed')
        ->assertSee('chain');

    expect(DB::table('chronicle_entries')->where('id', $middle->id)->exists())->toBeFalse();
});

it('breaks the chain with an alter (raw column update) and localizes the failure', function () {
    $entry = DB::table('chronicle_entries')->orderBy('sequence')->skip(2)->first();

    Livewire::test(TamperSimulator::class)
        ->call('selectEntry', $entry->id)
        ->call('alter')
        ->assertSet('tampered', true)
        ->assertSet('valid', false)
        ->assertSet('failureType', VerificationFailure::ColumnPayloadDivergence->value)
        ->assertSet('failedEntryId', $entry->id)
        ->assertSee('Verification failed');
});

it('restores the ledger on reset after a scrub', function () {
    $middle = DB::table('chronicle_entries')->orderBy('sequence')->skip(2)->first();

    $component = Livewire::test(TamperSimulator::class)
        ->call('selectEntry', $middle->id)
        ->call('scrub')
        ->assertSet('valid', false)
        ->call('restore')
        ->assertSet('tampered', false)
        ->assertSet('baselineValid', true);

    expect(DB::table('chronicle_entries')->where('id', $middle->id)->exists())->toBeTrue()
        ->and(app(LedgerVerifier::class)->run()->valid)->toBeTrue();
});

it('restores the ledger on reset after an alter', function () {
    $entry = DB::table('chronicle_entries')->orderBy('sequence')->skip(2)->first();
    $originalAction = $entry->action;

    Livewire::test(TamperSimulator::class)
        ->call('selectEntry', $entry->id)
        ->call('alter')
        ->assertSet('valid', false)
        ->call('restore')
        ->assertSet('tampered', false);

    expect(DB::table('chronicle_entries')->where('id', $entry->id)->value('action'))
        ->toBe($originalAction)
        ->and(app(LedgerVerifier::class)->run()->valid)->toBeTrue();
});
