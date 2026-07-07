<?php

use App\Models\Patient;
use App\Support\LedgerVerifier;
use App\Support\VerificationOutcome;
use Chronicle\Verification\VerificationFailure;
use Database\Seeders\ClinicianSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(fn () => $this->seed(ClinicianSeeder::class));

it('reports a valid outcome for an untampered ledger', function () {
    Patient::factory()->count(2)->create();

    $outcome = app(LedgerVerifier::class)->run();

    expect($outcome)->toBeInstanceOf(VerificationOutcome::class)
        ->and($outcome->valid)->toBeTrue()
        ->and($outcome->checked)->toBeGreaterThan(0)
        ->and($outcome->failureReason)->toBeNull()
        ->and($outcome->entryId)->toBeNull();
});

it('reports a failure with a human reason and the breaking entry id when an entry is tampered', function () {
    $patient = Patient::factory()->create();

    // Raw UPDATE bypasses Eloquent's immutability guard - exactly how a real
    // tamper would look: the stored payload no longer matches its payload_hash.
    $entry = DB::table('chronicle_entries')->orderBy('sequence')->first();
    DB::table('chronicle_entries')
        ->where('id', $entry->id)
        ->update(['payload' => json_encode(['tampered' => true])]);

    $outcome = app(LedgerVerifier::class)->run();

    expect($outcome->valid)->toBeFalse()
        ->and($outcome->failureType)->toBe(VerificationFailure::PayloadHashMismatch->value)
        ->and($outcome->failureReason)->toContain('payload')
        ->and($outcome->entryId)->toBe($entry->id);
});
