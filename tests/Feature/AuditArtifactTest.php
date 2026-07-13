<?php

use Chronicle\Filament\Support\ComplianceReportStore;
use Chronicle\Filament\Support\ExportArtifactStore;
use Database\Seeders\AuditArtifactSeeder;
use Database\Seeders\ClinicianSeeder;
use Database\Seeders\PatientSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    pinSigningKey();
});

it('pre-generates one verifiable export and one compliance report', function () {
    $this->seed([ClinicianSeeder::class, PatientSeeder::class, AuditArtifactSeeder::class]);

    expect(app(ExportArtifactStore::class)->all())->toHaveCount(1)
        ->and(app(ComplianceReportStore::class)->all())->toHaveCount(1);
});
