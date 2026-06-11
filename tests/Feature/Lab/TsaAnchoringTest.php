<?php

use App\Support\TsaAnchoring;
use Chronicle\Anchoring\Rfc3161TimestampAnchor;

it('reports unconfigured when the rfc3161 provider is absent', function () {
    config(['chronicle.anchoring.providers' => []]);

    expect(app(TsaAnchoring::class)->configured())->toBeFalse();
});

it('reports unconfigured when the TSA url is blank', function () {
    config(['chronicle.anchoring.providers.rfc3161' => [
        'provider' => Rfc3161TimestampAnchor::class,
        'tsa_url' => '',
        'tsa_certificate' => storage_path('tsa/cacert.pem'),
    ]]);

    expect(app(TsaAnchoring::class)->configured())->toBeFalse();
});

it('reports unconfigured when the certificate file is missing', function () {
    config(['chronicle.anchoring.providers.rfc3161' => [
        'provider' => Rfc3161TimestampAnchor::class,
        'tsa_url' => 'https://freetsa.org/tsr',
        'tsa_certificate' => storage_path('tsa/does-not-exist.pem'),
    ]]);

    expect(app(TsaAnchoring::class)->configured())->toBeFalse();
});

it('reports configured when provider, url and cert file are all present', function () {
    $cert = storage_path('app/lab/tsa-test-cert.pem');
    @mkdir(dirname($cert), 0775, true);
    file_put_contents($cert, 'PEM');

    config(['chronicle.anchoring.providers.rfc3161' => [
        'provider' => Rfc3161TimestampAnchor::class,
        'tsa_url' => 'https://freetsa.org/tsr',
        'tsa_certificate' => $cert,
    ]]);

    expect(app(TsaAnchoring::class)->configured())->toBeTrue();

    @unlink($cert);
});
