<?php

use Chronicle\Anchoring\AnchorManager;
use Chronicle\Anchoring\CheckpointAnchorer;
use Chronicle\Contracts\SigningProvider;
use Chronicle\Encryption\LocalKeyEncryptionProvider;
use Chronicle\Signing\Ed25519SigningProvider;
use Chronicle\Signing\KeyRing;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeTsaAnchor;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(DatabaseMigrations::class)
    ->in('Reset');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Pin a deterministic single-key Ed25519 ring so checkpoint signing works in any
 * environment (CI without a populated .env) and key ids are stable across tests.
 */
function pinSigningKey(): void
{
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
}

/**
 * Configure the deterministic RFC 3161 stand-in (Tests\Support\FakeTsaAnchor) so
 * TsaAnchoring::configured() reports true and anchoring uses the fake provider -
 * no network, no openssl. Shared by FullCompromiseTest and the reset tests.
 */
function configureFakeTsa(): void
{
    $cert = storage_path('app/lab/fake-tsa-cert.pem');
    @mkdir(dirname($cert), 0775, true);
    file_put_contents($cert, 'PEM');

    config(['chronicle.anchoring.providers.rfc3161' => [
        'provider' => FakeTsaAnchor::class,
        'tsa_url' => 'https://tsa.test/tsr',
        'tsa_certificate' => $cert,
    ]]);
    // AnchorManager is a fresh `bind`, but CheckpointAnchorer is a singleton that
    // captures an AnchorManager at first resolution - forget it, so it rebuilds
    // against the fake provider config set above.
    app()->forgetInstance(AnchorManager::class);
    app()->forgetInstance(CheckpointAnchorer::class);
}

/**
 * Turn on the local-KEK crypto-shredding for a test with a fixed base64 KEK so
 * entries recorded during the test encrypt deterministically. Mirrors the demo's
 * production toggle (config/chronicle.php + CHRONICLE_ENCRYPTION_* env).
 */
function enableDemoEncryption(): void
{
    config([
        'chronicle.encryption.enabled' => true,
        'chronicle.encryption.fields' => ['metadata', 'context', 'diff'],
        'chronicle.encryption.kek.provider' => LocalKeyEncryptionProvider::class,
        'chronicle.encryption.kek.key' => base64_encode(str_repeat('k', 32)),
        'chronicle.encryption.kek.id' => 'local',
    ]);
}
