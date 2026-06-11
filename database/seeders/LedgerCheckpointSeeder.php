<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Support\TsaAnchoring;
use Chronicle\Anchoring\CheckpointAnchorer;
use Chronicle\Checkpoints\CheckpointCreator;
use Illuminate\Database\Seeder;
use Throwable;

/**
 * Builds a small, deterministic checkpoint history on top of the synthetic
 * clinic data so the Ledger explorer and the Integrity Lab have substance the
 * moment the demo loads: two signed checkpoints, and — only when a real external
 * anchor is configured (TsaAnchoring::configured()) — an anchored latest
 * checkpoint so "Verify --anchors" has something to check. Never fakes an anchor.
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
        $creator = app(CheckpointCreator::class);

        // Checkpoint 1: seals the initial seeded activity (patients, encounters,
        // prescriptions created by PatientSeeder).
        $creator->create();

        // A single deterministic amendment so checkpoint 2 has new activity to seal.
        $mercury = Patient::query()->where('name', 'Mercury Vesper')->firstOrFail();
        $mercury->update(['notes' => 'Follow-up scheduled for next quarter.']);

        // Checkpoint 2: seals the amendment.
        $checkpoint = $creator->create();

        // Anchor the latest checkpoint only when a real TSA is configured.
        if (app(TsaAnchoring::class)->configured()) {
            app(CheckpointAnchorer::class)->anchor($checkpoint, 'rfc3161');
        }
    }
}
