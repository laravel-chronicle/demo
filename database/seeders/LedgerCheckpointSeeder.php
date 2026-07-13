<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Support\TsaAnchoring;
use Chronicle\Anchoring\CheckpointAnchorer;
use Chronicle\Checkpoints\CheckpointCreator;
use Chronicle\Contracts\SigningProvider;
use Chronicle\Signing\KeyRing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Builds a deterministic checkpoint history that also demonstrates signing-key
 * rotation: two checkpoints are sealed under key A (chronicle-dev-key), the
 * ledger is rotated to key B (chronicle-key-2), and the latest checkpoint is
 * sealed - and anchored when a real TSA is configured - under key B. This makes
 * the key-ring widget show a Retired key A and an Active key B, with each
 * checkpoint badged by the key that signed it. Never fakes an anchor.
 */
class LedgerCheckpointSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @throws Throwable
     */
    public function run(): void
    {
        // Seal the initial seeded activity under key A.
        $this->activateSigningKey('chronicle-dev-key');
        $creator = app(CheckpointCreator::class);

        // Checkpoint 1 (key A): seals PatientSeeder + any erasure/hold activity.
        $creator->create();

        // A deterministic amendment so checkpoint 2 seals new activity.
        $mercury = Patient::query()->where('name', 'Mercury Vesper')->firstOrFail();
        $mercury->update(['notes' => 'Follow-up scheduled for next quarter.']);

        // Checkpoint 2 (key A).
        $creator->create();

        // Rotate to key B: this creates a boundary checkpoint under key A and
        // prints activation instructions - it does NOT flip the active key, so
        // we activate key B ourselves for every subsequent checkpoint.
        Artisan::call('chronicle:key:rotate', ['newKeyId' => 'chronicle-key-2']);
        $this->activateSigningKey('chronicle-key-2');
        $creator = app(CheckpointCreator::class);

        // Another amendment so the post-rotation checkpoint seals real activity.
        $venus = Patient::query()->where('name', 'Venus Vesper')->firstOrFail();
        $venus->update(['notes' => 'Medication review completed.']);

        // Checkpoint 3 (key B): the latest checkpoint, signed under the new key.
        $checkpoint = $creator->create();

        // Anchor the latest checkpoint only when a real TSA is configured.
        if (app(TsaAnchoring::class)->configured()) {
            app(CheckpointAnchorer::class)->anchor($checkpoint, 'rfc3161');
        }
    }

    /**
     * Set the active signing key for subsequent checkpoints. KeyRing and
     * SigningProvider are container singletons, so they must be forgotten for
     * the change to take effect within a single seeder run.
     */
    protected function activateSigningKey(string $keyId): void
    {
        config(['chronicle.signing.active' => $keyId]);
        app()->forgetInstance(KeyRing::class);
        app()->forgetInstance(SigningProvider::class);
    }
}
