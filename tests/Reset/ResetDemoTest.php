<?php

use App\Livewire\ResetDemo;
use App\Models\Patient;
use App\Support\LedgerVerifier;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    pinSigningKey();
    // The throttle lives in the file store (it must survive demo:reset's
    // migrate:fresh, which wipes the database cache table). Clear it so each
    // test starts from a known state.
    Cache::store('file')->flush();
});

it('rebuilds a verifiable ledger and redirects home when pressed', function () {
    Livewire::test(ResetDemo::class)
        ->call('resetDemo')
        ->assertRedirect(route('home'));

    expect(Patient::query()->count())->toBe(6)
        ->and(app(LedgerVerifier::class)->run()->valid)->toBeTrue();
});

it('throttles repeated resets per IP', function () {
    $ip = request()->ip() ?? 'unknown';
    // Pre-fill the per-IP limit (3/hour) so the next press is blocked without
    // running another rebuild.
    Cache::store('file')->put('demo-reset:'.$ip, 3, now()->addHour());

    Livewire::test(ResetDemo::class)
        ->call('resetDemo')
        ->assertNoRedirect()
        ->assertSet('message', 'The demo was reset recently - please wait before resetting again.');
});
