<?php

use App\Models\Patient;
use App\Support\LedgerVerifier;
use Chronicle\Checkpoints\Checkpoint;
use Chronicle\Verification\AnchorVerifier;

beforeEach(function () {
    pinSigningKey();
});

it('rebuilds a deterministic, verifiable ledger with checkpoints', function () {
    $this->artisan('demo:reset')->assertSuccessful();

    expect(Patient::query()->count())->toBe(6)
        ->and(Checkpoint::query()->count())->toBe(2)
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
