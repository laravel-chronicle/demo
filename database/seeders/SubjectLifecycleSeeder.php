<?php

namespace Database\Seeders;

use App\Models\Patient;
use Chronicle\Facades\Chronicle;
use Chronicle\Lifecycle\LegalHold;
use Illuminate\Database\Seeder;

/**
 * Exercises the GDPR lifecycle so the crypto-shredding surface shows all three
 * states: one patient is crypto-shredded (Erased + a subject.erased proof), one
 * is placed under legal hold (On hold), and the rest stay Encrypted. Runs before
 * the checkpoint seeder so the erasure proof is sealed. Erasure destroys only the
 * subject DEK - existing entries are never mutated, so the chain still verifies.
 */
class SubjectLifecycleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $erased = Patient::query()->where('name', 'Neptune Vesper')->firstOrFail();
        $held = Patient::query()->where('name', 'Saturn Vesper')->firstOrFail();

        // Crypto-shred Neptune (GDPR Art. 17): destroys the DEK and appends a
        // PII-free subject.erased proof.
        Chronicle::eraseSubject(
            Patient::class,
            (string) $erased->getKey(),
            requester: 'Admin Vega',
            reason: 'GDPR Article 17 erasure request (demo).',
        );

        // Place a litigation hold on Saturn so erasure/pruning are blocked.
        LegalHold::place(
            Patient::class,
            (string) $held->getKey(),
            reason: 'Pending audit inquiry (demo).',
            by: 'Admin Vega',
        );
    }
}
