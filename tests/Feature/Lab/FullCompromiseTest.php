<?php

use App\Livewire\Lab\FullCompromise;
use App\Models\Patient;
use App\Support\LedgerVerifier;
use Chronicle\Checkpoints\Checkpoint;
use Chronicle\Contracts\SigningProvider;
use Chronicle\Signing\Ed25519SigningProvider;
use Chronicle\Signing\KeyRing;
use Chronicle\Verification\AnchorVerifier;
use Chronicle\Verification\VerificationFailure;
use Database\Seeders\ClinicianSeeder;
use Livewire\Livewire;

beforeEach(function () {
    // Deterministic single-key ring (real Ed25519 keypair) so re-signing the
    // forged checkpoints works without relying on the project's .env.
    config([
        'chronicle.signing.active' => 'chronicle-dev-key',
        'chronicle.signing.keys' => [
            'chronicle-dev-key' => [
                'provider' => Ed25519SigningProvider::class,
                'algorithm' => 'ed25519',
                'private_key' => '6ax+w8LH2V2GWU3YBPzi/6WNPpCQSYEZvzI+M0SMruuvRORm49DJuop8TRA6RNkRisac/Gta+ZwsvzFSbLUAhA==',
                'public_key' => 'r0TkZuPQybqKfE0QOkTZEYrGnPxrWvmcLL8xUmy1AIQ=',
            ],
        ],
    ]);
    app()->forgetInstance(KeyRing::class);
    app()->forgetInstance(SigningProvider::class);

    $this->seed(ClinicianSeeder::class);
});

it('shows an honest placeholder when no TSA is configured', function () {
    config(['chronicle.anchoring.providers' => []]);

    Livewire::test(FullCompromise::class)
        ->assertSet('configured', false)
        ->assertSee('Configure a TSA');
});

it('passes offline checkpoints-only verify but fails --anchors after a full compromise', function () {
    configureFakeTsa();
    Patient::factory()->count(2)->create();

    $component = Livewire::test(FullCompromise::class)
        ->assertSet('configured', true)
        ->call('buildAnchoredLedger')
        ->assertSet('step', 1);

    // Honesty check: the freshly anchored ledger verifies under --anchors BEFORE tampering.
    $anchored = Checkpoint::query()
        ->whereHas('anchors', fn ($q) => $q->where('status', 'anchored'))->get();
    expect($anchored)->not->toBeEmpty()
        ->and(app(AnchorVerifier::class)->verify($anchored)->isValid())->toBeTrue();

    $component->call('compromise')->assertSet('step', 2);

    // Offline (--checkpoints-only): the forgery is internally consistent -> PASSES.
    $component->call('verifyOffline')->assertSet('offlineValid', true);

    // --anchors: the TSA token bound the ORIGINAL digest -> FAILS at the anchored checkpoint.
    $component->call('verifyAnchors')
        ->assertSet('anchorsValid', false)
        ->assertSet('anchorsFailureType', VerificationFailure::AnchorInvalid->value);

    expect($component->get('anchorsFailedCheckpointId'))->not->toBeNull();
});

it('restores the ledger and removes the anchored checkpoint on reset', function () {
    configureFakeTsa();
    Patient::factory()->count(2)->create();

    $component = Livewire::test(FullCompromise::class)
        ->call('buildAnchoredLedger')
        ->call('compromise');

    $checkpointId = $component->get('createdCheckpointId');

    $component->call('restore')->assertSet('step', 0);

    expect(Checkpoint::query()->whereKey($checkpointId)->exists())->toBeFalse()
        ->and(app(LedgerVerifier::class)->run()->valid)->toBeTrue();
});
