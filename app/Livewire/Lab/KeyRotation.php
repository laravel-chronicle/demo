<?php

namespace App\Livewire\Lab;

use App\Livewire\Lab\Concerns\ThrottlesDestructiveActions;
use App\Models\Patient;
use App\Support\LabSandbox;
use App\Support\LedgerVerifier;
use Chronicle\Checkpoints\Checkpoint;
use Chronicle\Checkpoints\CheckpointCreator;
use Chronicle\Contracts\SigningProvider;
use Chronicle\Signing\KeyRing;
use Illuminate\Contracts\View\View;
use JsonException;
use Livewire\Component;
use Throwable;

class KeyRotation extends Component
{
    use ThrottlesDestructiveActions;

    public const string NEW_KEY_ID = 'chronicle-key-2';

    /** @var list<array{key_id: string, algorithm: string, active: bool}> */
    public array $keys = [];

    public string $activeKeyId = '';

    public ?string $originalActiveKeyId = null;

    public bool $rotated = false;

    /** @var array{id: string, key_id: string, algorithm: string, chain_hash: string}|null */
    public ?array $epoch1Checkpoint = null;

    /** @var array{id: string, key_id: string, algorithm: string, chain_hash: string}|null */
    public ?array $epoch2Checkpoint = null;

    public bool $verified = false;

    public bool $valid = false;

    public int $checked = 0;

    public function mount(): void
    {
        $this->refreshRing();
        $this->originalActiveKeyId = $this->activeKeyId;
    }

    /**
     * Create an epoch-1 checkpoint under the current key, switch the active key to
     * key 2 at runtime, then create an epoch-2 (boundary) checkpoint under key 2.
     *
     * @throws Throwable
     */
    public function rotate(CheckpointCreator $creator): void
    {
        if (! $this->passesThrottle('rotate')) {
            return;
        }

        // Epoch 1: checkpoint signed by the currently active key.
        $first = $creator->create();
        $this->epoch1Checkpoint = $this->describe($first);

        // Switch the active signing key for the rest of this request.
        config(['chronicle.signing.active' => self::NEW_KEY_ID]);
        app()->forgetInstance(KeyRing::class);
        app()->forgetInstance(SigningProvider::class);

        // Add a fresh entry so the head advances, then checkpoint under key 2.
        Patient::factory()->create();
        $second = app(CheckpointCreator::class)->create();
        $this->epoch2Checkpoint = $this->describe($second);

        $this->rotated = true;
        $this->refreshRing();
    }

    /**
     * @throws JsonException
     */
    public function verify(LedgerVerifier $verifier): void
    {
        $outcome = $verifier->run();
        $this->verified = true;
        $this->valid = $outcome->valid;
        $this->checked = $outcome->checked;
    }

    public function restore(LabSandbox $sandbox): void
    {
        $ids = [];
        foreach ([$this->epoch1Checkpoint, $this->epoch2Checkpoint] as $cp) {
            if ($cp !== null) {
                $ids[] = $cp['id'];
            }
        }
        $sandbox->forgetCheckpoints($ids);

        config(['chronicle.signing.active' => $this->originalActiveKeyId]);
        app()->forgetInstance(KeyRing::class);
        app()->forgetInstance(SigningProvider::class);

        $this->reset('rotated', 'epoch1Checkpoint', 'epoch2Checkpoint', 'verified', 'valid', 'checked');
        $this->refreshRing();
    }

    /**
     * @return array{id: string, key_id: string, algorithm: string, chain_hash: string}
     */
    private function describe(Checkpoint $checkpoint): array
    {
        return [
            'id' => $checkpoint->id,
            'key_id' => $checkpoint->key_id,
            'algorithm' => $checkpoint->algorithm,
            'chain_hash' => $checkpoint->chain_hash,
        ];
    }

    private function refreshRing(): void
    {
        $active = config('chronicle.signing.active');
        $activeId = is_string($active) ? $active : '';
        $this->activeKeyId = $activeId;

        $keys = [];
        foreach (array_keys(app(KeyRing::class)->all()) as $label) {
            // KeyRing::all() is keyed "algorithm:keyId".
            $parts = explode(':', (string) $label, 2);
            $keyId = $parts[1] ?? (string) $label;
            $keys[] = [
                'key_id' => $keyId,
                'algorithm' => $parts[0],
                'active' => $keyId === $activeId,
            ];
        }
        $this->keys = $keys;
    }

    public function render(): View
    {
        return view('livewire.lab.key-rotation');
    }
}
