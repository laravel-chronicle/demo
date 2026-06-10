<?php

use App\Livewire\Lab\KeyRotation;
use App\Models\Patient;
use App\Support\LedgerVerifier;
use Chronicle\Checkpoints\Checkpoint;
use Chronicle\Contracts\SigningProvider;
use Chronicle\Signing\Ed25519SigningProvider;
use Chronicle\Signing\KeyRing;
use Database\Seeders\ClinicianSeeder;
use Livewire\Livewire;

beforeEach(function () {
    // Deterministic two-key ring (real Ed25519 keypairs) so rotation is testable
    // without relying on the project's .env.
    config([
        'chronicle.signing.active' => 'chronicle-dev-key',
        'chronicle.signing.keys' => [
            'chronicle-dev-key' => [
                'provider' => Ed25519SigningProvider::class,
                'algorithm' => 'ed25519',
                'private_key' => '6ax+w8LH2V2GWU3YBPzi/6WNPpCQSYEZvzI+M0SMruuvRORm49DJuop8TRA6RNkRisac/Gta+ZwsvzFSbLUAhA==',
                'public_key' => 'r0TkZuPQybqKfE0QOkTZEYrGnPxrWvmcLL8xUmy1AIQ=',
            ],
            'chronicle-key-2' => [
                'provider' => Ed25519SigningProvider::class,
                'algorithm' => 'ed25519',
                'private_key' => 'xjasR82zgzUYM82TC32ntvoP1D69cEP2dPcxxzuuIRei5nwBhw450d4SV/SAjais5rlJhvPK1Zyl33vwAVblDA==',
                'public_key' => 'ouZ8AYcOOdHeElf0gI2orOa5SYbzytWcpd978AFW5Qw=',
            ],
        ],
    ]);
    app()->forgetInstance(KeyRing::class);
    app()->forgetInstance(SigningProvider::class);

    $this->seed(ClinicianSeeder::class);
    Patient::factory()->count(2)->create();
});

it('lists both keys in the ring', function () {
    Livewire::test(KeyRotation::class)
        ->assertSet('activeKeyId', 'chronicle-dev-key')
        ->assertSee('chronicle-dev-key')
        ->assertSee('chronicle-key-2');
});

it('rotates to key 2 and keeps the whole ledger verifiable across both epochs', function () {
    $component = Livewire::test(KeyRotation::class)
        ->call('rotate')
        ->assertSet('rotated', true)
        ->call('verify')
        ->assertSet('valid', true)
        ->assertSee('Pre-rotation checkpoints still verify');

    $epoch1 = $component->get('epoch1Checkpoint');
    $epoch2 = $component->get('epoch2Checkpoint');

    expect($epoch1['key_id'])->toBe('chronicle-dev-key')
        ->and($epoch2['key_id'])->toBe('chronicle-key-2')
        ->and(Checkpoint::query()->whereKey($epoch1['id'])->exists())->toBeTrue()
        ->and(Checkpoint::query()->whereKey($epoch2['id'])->exists())->toBeTrue()
        ->and(app(LedgerVerifier::class)->run()->valid)->toBeTrue();
});

it('removes the rotation checkpoints and restores the active key on reset', function () {
    $component = Livewire::test(KeyRotation::class)
        ->call('rotate')
        ->assertSet('rotated', true);

    $epoch1Id = $component->get('epoch1Checkpoint')['id'];
    $epoch2Id = $component->get('epoch2Checkpoint')['id'];

    $component->call('restore')
        ->assertSet('rotated', false)
        ->assertSet('activeKeyId', 'chronicle-dev-key');

    expect(Checkpoint::query()->whereKey($epoch1Id)->exists())->toBeFalse()
        ->and(Checkpoint::query()->whereKey($epoch2Id)->exists())->toBeFalse();
});
