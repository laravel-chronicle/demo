<?php

namespace App\Livewire\Lab;

use App\Livewire\Lab\Concerns\ThrottlesDestructiveActions;
use App\Models\Patient;
use App\Support\LabSandbox;
use Chronicle\Anchoring\NullAnchor;
use Chronicle\Checkpoints\Checkpoint;
use Chronicle\Checkpoints\CheckpointCreator;
use Chronicle\Entry\Entry;
use Chronicle\Exports\ExportManager;
use Chronicle\Verification\ExportVerifier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use JsonException;
use Livewire\Component;
use Throwable;

class LifecycleStepper extends Component
{
    use ThrottlesDestructiveActions;

    public int $step = 0;

    public int $generatedEntries = 0;

    /** @var array<string, mixed>|null */
    public ?array $checkpoint = null;

    /** @var array<string, mixed>|null */
    public ?array $anchorReceipt = null;

    /** @var array<string, mixed>|null */
    public ?array $exportManifest = null;

    public bool $exportVerified = false;

    public ?string $createdCheckpointId = null;

    public ?string $exportPath = null;

    public function generateActivity(): void
    {
        if (! $this->passesThrottle('lifecycle')) {
            return;
        }

        $before = Entry::query()->count();
        Patient::factory()->count(2)->create();
        $this->generatedEntries = Entry::query()->count() - $before;
        $this->step = max($this->step, 1);
    }

    /**
     * @throws Throwable
     */
    public function createCheckpoint(CheckpointCreator $creator): void
    {
        if (! $this->passesThrottle('lifecycle')) {
            return;
        }

        $checkpoint = $creator->create();

        $this->createdCheckpointId = $checkpoint->id;
        $this->checkpoint = [
            'id' => $checkpoint->id,
            'chain_hash' => $checkpoint->chain_hash,
            'algorithm' => $checkpoint->algorithm,
            'key_id' => $checkpoint->key_id,
            'entry_count' => $checkpoint->entry_count,
            'created_at' => $checkpoint->created_at->toIso8601String(),
        ];
        $this->step = max($this->step, 2);
    }

    public function anchor(): void
    {
        if ($this->createdCheckpointId === null || ! $this->passesThrottle('anchor')) {
            return;
        }

        $checkpoint = Checkpoint::query()->findOrFail($this->createdCheckpointId);
        $receipt = (new NullAnchor)->anchor($checkpoint);

        $this->anchorReceipt = [
            'provider' => $receipt->provider,
            'reference' => $receipt->reference,
            'proof' => $receipt->proof,
            'anchored_at' => $receipt->anchoredAt->format(DATE_ATOM),
        ];
        $this->step = max($this->step, 3);
    }

    /**
     * @throws JsonException
     */
    public function export(ExportManager $manager): void
    {
        if (! $this->passesThrottle('lifecycle')) {
            return;
        }

        $dir = storage_path('app/lab');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $path = $dir.'/export-'.Str::ulid().'.json';
        $result = $manager->export($path);

        $this->exportPath = $path;
        $this->exportManifest = [
            'path' => $path,
            'entry_count' => $result->entryCount,
            'dataset_hash' => $result->datasetHash,
            'chain_head' => $result->chainHead,
        ];
        $this->step = max($this->step, 4);
    }

    /**
     * @throws JsonException
     */
    public function verifyExport(ExportVerifier $verifier): void
    {
        if ($this->exportPath === null) {
            return;
        }

        $this->exportVerified = $verifier->verify($this->exportPath)->isValid();
        $this->step = max($this->step, 5);
    }

    public function restore(LabSandbox $sandbox): void
    {
        if ($this->createdCheckpointId !== null) {
            $sandbox->forgetCheckpoints([$this->createdCheckpointId]);
        }

        $sandbox->deletePath($this->exportPath);

        $this->reset('step', 'generatedEntries', 'checkpoint', 'anchorReceipt', 'exportManifest',
            'exportVerified', 'createdCheckpointId', 'exportPath');
    }

    public function render(): View
    {
        return view('livewire.lab.lifecycle-stepper');
    }
}
