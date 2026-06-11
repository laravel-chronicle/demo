<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Support\LedgerVerifier;
use App\Support\TsaAnchoring;
use Chronicle\Checkpoints\Checkpoint;
use Chronicle\Entry\Entry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use JsonException;

#[Signature('demo:reset')]
#[Description('Rebuild the public demo: fresh schema, deterministic synthetic data, signed checkpoints, and (when a TSA is configured) an anchored checkpoint - leaving a verifiable ledger.')]
class DemoReset extends Command
{
    /**
     * Execute the console command.
     *
     * @throws JsonException
     */
    public function handle(LedgerVerifier $verifier, TsaAnchoring $tsa): int
    {
        $this->callSilent('migrate:fresh', ['--force' => true]);
        $this->callSilent('db:seed', ['--force' => true]);

        $outcome = $verifier->run();

        $this->newLine();
        $this->components->twoColumnDetail('Patients', (string) Patient::query()->count());
        $this->components->twoColumnDetail('Ledger entries', (string) Entry::query()->count());
        $this->components->twoColumnDetail('Checkpoints', (string) Checkpoint::query()->count());
        $this->components->twoColumnDetail(
            'External anchor',
            $tsa->configured() ? 'configured (RFC 3161 TSA)' : 'not configured',
        );
        $this->components->twoColumnDetail(
            'Ledger verifies',
            $outcome->valid
                ? '<fg=green>✓ valid</> ('.$outcome->checked.' checked)'
                : '<fg=red>✗ '.($outcome->failureType ?? 'invalid').'</>',
        );

        return $outcome->valid ? self::SUCCESS : self::FAILURE;
    }
}
