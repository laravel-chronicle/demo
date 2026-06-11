<?php

namespace App\Support;

/**
 * View-friendly snapshot of a Chronicle ledger verification run.
 *
 * Plain, immutable data the Livewire component and Blade view can read
 * without touching Chronicle's internal result object.
 */
final readonly class VerificationOutcome
{
    public function __construct(
        public bool $valid,
        public int $checked,
        public ?string $failureType = null,
        public ?string $failureReason = null,
        public ?string $entryId = null,
    ) {}
}
