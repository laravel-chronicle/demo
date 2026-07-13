<?php

namespace App\Support;

/**
 * Honest gate for Integrity Lab panel 4e. External anchoring is only considered
 * "configured" when the RFC 3161 provider is registered, a TSA URL is set, and
 * the offline verification certificate actually exists on disk. Without all
 * three, the panel must show an explanatory placeholder rather than a fake pass
 * (a NullAnchor-style proof living in the same database the attacker controls
 * would falsely succeed - exactly what §4e forbids).
 */
class TsaAnchoring
{
    public function configured(): bool
    {
        $provider = config('chronicle.anchoring.providers.rfc3161');

        if (! is_array($provider)) {
            return false;
        }

        $url = $provider['tsa_url'] ?? null;
        $cert = $provider['tsa_certificate'] ?? null;

        return is_string($url) && $url !== ''
            && is_string($cert) && is_file($cert);
    }

    /**
     * The configured verification certificate path, if any.
     */
    public function certificatePath(): ?string
    {
        $provider = config('chronicle.anchoring.providers.rfc3161');
        $cert = is_array($provider) ? ($provider['tsa_certificate'] ?? null) : null;

        return is_string($cert) && $cert !== '' ? $cert : null;
    }

    public function tsaUrl(): ?string
    {
        $provider = config('chronicle.anchoring.providers.rfc3161');
        $url = is_array($provider) ? ($provider['tsa_url'] ?? null) : null;

        return is_string($url) && $url !== '' ? $url : null;
    }
}
