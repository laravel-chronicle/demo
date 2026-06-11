<?php

namespace App\Support;

use Chronicle\Verification\IntegrityVerifier;
use Chronicle\Verification\VerificationFailure;
use JsonException;

class LedgerVerifier
{
    public function __construct(private IntegrityVerifier $verifier) {}

    /**
     * Run a full integrity verification of the ledger and return a
     * view-friendly outcome.
     *
     * @throws JsonException
     */
    public function run(): VerificationOutcome
    {
        $result = $this->verifier->verify();

        if ($result->isValid()) {
            return new VerificationOutcome(valid: true, checked: $result->checked());
        }

        $type = $result->failureType();

        return new VerificationOutcome(
            valid: false,
            checked: $result->checked(),
            failureType: $type,
            failureReason: $this->humanReason($type),
            entryId: $result->entryId(),
        );
    }

    /**
     * Translate a Chronicle failure code into a plain-English explanation.
     */
    private function humanReason(?string $type): string
    {
        return match ($type) {
            VerificationFailure::PayloadHashMismatch->value => "An entry's stored payload no longer matches its recorded payload hash.",
            VerificationFailure::ChainHashMismatch->value => "The hash chain is broken — an entry's link to its predecessor does not recompute.",
            VerificationFailure::ColumnPayloadDivergence->value => "An entry's columns were altered out of sync with its signed payload.",
            VerificationFailure::CheckpointMissing->value => 'A checkpoint referenced by an entry is missing.',
            VerificationFailure::CheckpointSignatureInvalid->value => 'A checkpoint signature failed verification.',
            VerificationFailure::UnknownKey->value => 'A checkpoint was signed with a key that is not in the key ring.',
            default => 'The ledger failed integrity verification.',
        };
    }
}
