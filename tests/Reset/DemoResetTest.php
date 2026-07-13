<?php

use App\Models\Patient;
use App\Support\LedgerVerifier;
use Chronicle\Checkpoints\Checkpoint;
use Chronicle\Verification\AnchorVerifier;

beforeEach(function () {
    // The checkpoint seeder now rotates key A -> key B, so both keys must be in
    // the ring for signing to succeed.
    pinTwoSigningKeys();
});

it('rebuilds a deterministic, verifiable ledger with checkpoints', function () {
    // Anchoring is enabled app-wide; clear the providers so the rebuild stays
    // offline and deterministic (anchoring behaviour is covered by the two
    // cases below).
    config(['chronicle.anchoring.providers' => []]);

    $this->artisan('demo:reset')->assertSuccessful();

    // Three checkpoints: two sealed under key A, then one under key B after the
    // rotation. (The rotate command's boundary checkpoint is a no-op here - no
    // new entries were recorded between checkpoint 2 and the rotation.)
    expect(Patient::query()->count())->toBe(6)
        ->and(Checkpoint::query()->count())->toBe(3)
        ->and(app(LedgerVerifier::class)->run()->valid)->toBeTrue();
});

it('anchors the latest checkpoint when a TSA is configured', function () {
    configureFakeTsa();

    $this->artisan('demo:reset')->assertSuccessful();

    $anchored = Checkpoint::query()
        ->whereHas('anchors', fn ($q) => $q->where('status', 'anchored'))
        ->get();

    expect($anchored)->not->toBeEmpty()
        ->and(app(AnchorVerifier::class)->verify($anchored)->isValid())->toBeTrue();
});

it('does not anchor when no TSA is configured', function () {
    config(['chronicle.anchoring.providers' => []]);

    $this->artisan('demo:reset')->assertSuccessful();

    expect(Checkpoint::query()->whereHas('anchors', fn ($q) => $q->where('status', 'anchored'))->count())
        ->toBe(0);
});
