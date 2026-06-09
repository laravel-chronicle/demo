<?php

use Illuminate\Support\Facades\Schema;

it('migrates the chronicle ledger tables', function () {
    expect(Schema::hasTable('chronicle_entries'))->toBeTrue()
        ->and(Schema::hasTable('chronicle_checkpoints'))->toBeTrue();
});

it('enables the read-only chronicle ui without auth middleware', function () {
    expect(config('chronicle.ui.enabled'))->toBeTrue()
        ->and(config('chronicle.ui.middleware'))->toBe(['web']);
});
