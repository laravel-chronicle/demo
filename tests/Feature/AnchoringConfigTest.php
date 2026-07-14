<?php

use App\Support\TsaAnchoring;

it('has external anchoring enabled', function () {
    expect(config('chronicle.anchoring.enabled'))->toBeTrue();
});

it('registers the rfc3161 provider with a TSA url and an on-disk certificate', function () {
    // The demo config reads CHRONICLE_TSA_URL / _CERTIFICATE from the environment;
    // force a known-good pair so the assertion does not depend on the machine's .env.
    config([
        'chronicle.anchoring.providers.rfc3161.tsa_url' => 'https://freetsa.org/tsr',
        'chronicle.anchoring.providers.rfc3161.tsa_certificate' => storage_path('tsa/cacert.pem'),
    ]);

    expect(app(TsaAnchoring::class)->configured())->toBeTrue();
});
