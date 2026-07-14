<?php

namespace App\Livewire\Lab;

use App\Livewire\Lab\Concerns\ThrottlesDestructiveActions;
use App\Models\Patient;
use App\Support\LabSandbox;
use App\Support\TsaAnchoring;
use Chronicle\Anchoring\CheckpointAnchorer;
use Chronicle\Checkpoints\Checkpoint;
use Chronicle\Checkpoints\CheckpointCreator;
use Chronicle\Contracts\SigningProvider;
use Chronicle\Entry\Entry;
use Chronicle\Hashing\ChainHasher;
use Chronicle\Support\CanonicalPayloadSerializer;
use Chronicle\Verification\AnchorVerifier;
use Chronicle\Verification\CheckpointChainVerifier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use JsonException;
use Livewire\Component;
use Throwable;

class FullCompromise extends Component
{
    use ThrottlesDestructiveActions;

    /** True only when a real TSA anchor is configured (provider + url + cert file). */
    public bool $configured = false;

    /** 0 = idle, 1 = anchored ledger built, 2 = compromised, 3+ = verified. */
    public int $step = 0;

    public ?string $createdCheckpointId = null;

    /** @var list<string> */
    public array $anchoredCheckpointIds = [];

    /** @var array{provider: string, reference: ?string, proof: ?string, anchored_at: string}|null */
    public ?array $anchor = null;

    /** @var array{id: string, before: string, after: string}|null */
    public ?array $target = null;

    public ?bool $offlineValid = null;

    public int $offlineChecked = 0;

    public ?bool $anchorsValid = null;

    public ?string $anchorsFailedCheckpointId = null;

    public ?string $anchorsFailureType = null;

    /** @var list<array{id: string, payload: string, payload_hash: string, chain_hash: string, metadata: string}> */
    public array $entrySnapshots = [];

    /** @var list<array{id: string, chain_hash: string, signature: string, algorithm: string, key_id: string}> */
    public array $checkpointSnapshots = [];

    public function mount(TsaAnchoring $tsa): void
    {
        $this->configured = $tsa->configured();
    }

    /**
     * Build a real, externally-anchored ledger: generate activity, checkpoint the
     * head, and anchor that checkpoint with the configured RFC 3161 TSA.
     *
     * @throws Throwable
     */
    public function buildAnchoredLedger(CheckpointCreator $creator, CheckpointAnchorer $anchorer): void
    {
        if (! $this->configured || ! $this->passesThrottle('compromise')) {
            return;
        }

        Patient::factory()->count(2)->create();

        $checkpoint = $creator->create();
        $this->createdCheckpointId = $checkpoint->id;

        $row = $anchorer->anchor($checkpoint, 'rfc3161');
        $this->anchoredCheckpointIds = [$checkpoint->id];
        $this->anchor = [
            'provider' => $row->provider,
            'reference' => $row->reference,
            'proof' => $row->proof,
            'anchored_at' => $row->anchored_at?->toIso8601String() ?? '',
        ];

        $this->step = 1;
    }

    /**
     * Simulate an attacker with DB AND signing-key access: rewrite an entry's
     * payload, recompute the whole hash chain, and re-sign EVERY checkpoint with
     * a valid ring key. Every write is a raw query bypassing Chronicle.
     *
     * @throws JsonException|Throwable
     */
    public function compromise(
        CanonicalPayloadSerializer $serializer,
        ChainHasher $hasher,
        SigningProvider $signer,
    ): void {
        if ($this->step < 1 || ! $this->passesThrottle('compromise')) {
            return;
        }

        $this->snapshot();

        // Target the FIRST entry so every checkpoint head sits at/after it and
        // every anchored checkpoint's chain_hash genuinely changes.
        /** @var Entry $target */
        $target = Entry::query()->orderBy('sequence')->firstOrFail();

        $forgedPayload = $target->payload;
        $before = json_encode($forgedPayload['metadata'] ?? [], JSON_THROW_ON_ERROR);
        $forgedPayload['metadata'] = ['forged' => true, 'note' => 'access scrubbed by attacker'];
        $after = json_encode($forgedPayload['metadata'], JSON_THROW_ON_ERROR);

        // Re-link the chain from the target forward.
        $previousChainValue = Entry::query()
            ->where('sequence', '<', $target->sequence)
            ->orderByDesc('sequence')
            ->value('chain_hash');
        $previousChain = is_string($previousChainValue) ? $previousChainValue : ChainHasher::GENESIS;

        $isTarget = true;
        foreach (
            Entry::query()->where('sequence', '>=', $target->sequence)->orderBy('sequence')->cursor() as $entry
        ) {
            if ($isTarget) {
                $payloadHash = hash('sha256', $serializer->serialize($forgedPayload));
                $chainHash = $hasher->hash($previousChain, $payloadHash);

                DB::table('chronicle_entries')->where('id', $entry->id)->update([
                    'payload' => json_encode($forgedPayload, JSON_THROW_ON_ERROR),
                    'payload_hash' => $payloadHash,
                    'chain_hash' => $chainHash,
                    'metadata' => json_encode($forgedPayload['metadata'], JSON_THROW_ON_ERROR),
                ]);

                $isTarget = false;
            } else {
                // Payload untouched -> payload_hash stands; only the chain link moves.
                $chainHash = $hasher->hash($previousChain, (string) $entry->payload_hash);
                DB::table('chronicle_entries')->where('id', $entry->id)->update(['chain_hash' => $chainHash]);
            }

            $previousChain = $chainHash;
        }

        // Re-sign every checkpoint against its head's new chain_hash with the active key.
        foreach (Checkpoint::query()->orderBy('created_at')->orderBy('id')->cursor() as $checkpoint) {
            $headChainValue = $checkpoint->head_id === null
                ? $checkpoint->chain_hash
                : Entry::query()->whereKey($checkpoint->head_id)->value('chain_hash');
            $headChain = is_string($headChainValue) ? $headChainValue : $checkpoint->chain_hash;

            $signaturePayload = CheckpointCreator::signaturePayload(
                id: $checkpoint->id,
                chainHash: $headChain,
                algorithm: $signer->algorithm(),
                keyId: $signer->keyId(),
                createdAt: $checkpoint->created_at->getTimestamp(),
            );

            DB::table('chronicle_checkpoints')->where('id', $checkpoint->id)->update([
                'chain_hash' => $headChain,
                'algorithm' => $signer->algorithm(),
                'key_id' => $signer->keyId(),
                'signature' => $signer->sign($signaturePayload),
            ]);
        }

        $this->target = ['id' => $target->id, 'before' => $before, 'after' => $after];
        $this->step = 2;
    }

    /**
     * Offline verify (--checkpoints-only): signatures + head linkage, no per-entry
     * recompute. The internally-consistent forgery PASSES.
     *
     * @throws JsonException
     */
    public function verifyOffline(CheckpointChainVerifier $verifier): void
    {
        if ($this->step < 2) {
            return;
        }

        $result = $verifier->verify();
        $this->offlineValid = $result->isValid();
        $this->offlineChecked = $result->checked();
        $this->step = max($this->step, 3);
    }

    /**
     * Verify --anchors: re-checks each anchored checkpoint's TSA token. Because the
     * forged chain changed every checkpoint digest, the token no longer matches and
     * this FAILS at the first anchored checkpoint.
     */
    public function verifyAnchors(AnchorVerifier $verifier): void
    {
        if ($this->step < 2) {
            return;
        }

        $checkpoints = Checkpoint::query()
            ->whereHas('anchors', fn ($q) => $q->where('status', 'anchored'))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $result = $verifier->verify($checkpoints);
        $this->anchorsValid = $result->isValid();
        $this->anchorsFailedCheckpointId = $result->entryId();
        $this->anchorsFailureType = $result->failureType();
        $this->step = max($this->step, 4);
    }

    public function restore(LabSandbox $sandbox): void
    {
        foreach ($this->entrySnapshots as $snap) {
            DB::table('chronicle_entries')->where('id', $snap['id'])->update([
                'payload' => $snap['payload'],
                'payload_hash' => $snap['payload_hash'],
                'chain_hash' => $snap['chain_hash'],
                'metadata' => $snap['metadata'],
            ]);
        }

        foreach ($this->checkpointSnapshots as $snap) {
            DB::table('chronicle_checkpoints')->where('id', $snap['id'])->update([
                'chain_hash' => $snap['chain_hash'],
                'signature' => $snap['signature'],
                'algorithm' => $snap['algorithm'],
                'key_id' => $snap['key_id'],
            ]);
        }

        $sandbox->forgetAnchors($this->anchoredCheckpointIds);
        if ($this->createdCheckpointId !== null) {
            $sandbox->forgetCheckpoints([$this->createdCheckpointId]);
        }

        $this->reset('step', 'createdCheckpointId', 'anchoredCheckpointIds', 'anchor', 'target',
            'offlineValid', 'offlineChecked', 'anchorsValid', 'anchorsFailedCheckpointId',
            'anchorsFailureType', 'entrySnapshots', 'checkpointSnapshots');
    }

    /**
     * Snapshot every entry and checkpoint so reset can restore the ledger
     * precisely. Stored byte order is irrelevant: verification canonicalizes the
     * decoded payload, and we also restore payload_hash/chain_hash verbatim.
     *
     * @throws JsonException
     */
    private function snapshot(): void
    {
        $entries = [];
        foreach (Entry::query()->orderBy('sequence')->get() as $entry) {
            $entries[] = [
                'id' => $entry->id,
                'payload' => json_encode($entry->payload, JSON_THROW_ON_ERROR),
                'payload_hash' => $entry->payload_hash,
                'chain_hash' => $entry->chain_hash,
                'metadata' => json_encode($entry->metadata, JSON_THROW_ON_ERROR),
            ];
        }
        $this->entrySnapshots = $entries;

        $checkpoints = [];
        foreach (Checkpoint::query()->get() as $checkpoint) {
            $checkpoints[] = [
                'id' => $checkpoint->id,
                'chain_hash' => $checkpoint->chain_hash,
                'signature' => $checkpoint->signature,
                'algorithm' => $checkpoint->algorithm,
                'key_id' => $checkpoint->key_id,
            ];
        }
        $this->checkpointSnapshots = $checkpoints;
    }

    public function render(): View
    {
        return view('livewire.lab.full-compromise');
    }
}
