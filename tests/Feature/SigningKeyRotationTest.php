<?php

use Database\Seeders\ClinicianSeeder;
use Database\Seeders\LedgerCheckpointSeeder;
use Database\Seeders\PatientSeeder;
use Illuminate\Support\Facades\DB;

it('seals checkpoints under both the retired key A and the active key B', function () {
    pinTwoSigningKeys();
    // Keep the seeder fully offline. Anchoring is enabled app-wide (Task 1), so
    // every checkpoint auto-anchors via the registered providers; clearing them
    // stops both the auto-anchor jobs and the seeder's explicit anchor (no
    // network, no null-url construction error).
    config(['chronicle.anchoring.providers' => []]);

    $this->seed([ClinicianSeeder::class, PatientSeeder::class, LedgerCheckpointSeeder::class]);

    $keyIds = DB::table('chronicle_checkpoints')->pluck('key_id')->unique()->values();

    expect($keyIds)->toContain('chronicle-dev-key')
        ->and($keyIds)->toContain('chronicle-key-2')
        ->and(config('chronicle.signing.active'))->toBe('chronicle-key-2');
});
