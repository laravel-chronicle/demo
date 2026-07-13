<?php

namespace Tests\Support;

use Chronicle\Anchoring\AnchorReceipt;
use Chronicle\Anchoring\CheckpointDigest;
use Chronicle\Checkpoints\Checkpoint;
use Chronicle\Contracts\AnchorProvider;

/**
 * A deterministic stand-in for the RFC 3161 TSA used in tests - the "recorded
 * token fixture" the spec calls for. anchor() records the checkpoint digest the
 * (pretend) timestamp token covers; verify() passes only while the checkpoint's
 * digest still equals it. This reproduces the real anchor's guarantee - the TSA
 * token binds the original digest and cannot be re-issued by an attacker - with
 * no HTTP and no openssl, so it is stable in CI.
 *
 * @param  array<string, mixed>  $config
 */
class FakeTsaAnchor implements AnchorProvider
{
    public function __construct(private array $config = []) {}

    public function name(): string
    {
        return 'rfc3161';
    }

    public function anchor(Checkpoint $checkpoint): AnchorReceipt
    {
        return new AnchorReceipt(
            provider: $this->name(),
            reference: 'fake-tsa',
            proof: CheckpointDigest::for($checkpoint),
            anchoredAt: now()->toImmutable(),
        );
    }

    public function verify(Checkpoint $checkpoint, AnchorReceipt $receipt): bool
    {
        return $receipt->proof !== null
            && hash_equals($receipt->proof, CheckpointDigest::for($checkpoint));
    }
}
