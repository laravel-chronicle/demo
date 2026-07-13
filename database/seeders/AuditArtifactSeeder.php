<?php

namespace Database\Seeders;

use Chronicle\Exports\ExportManager;
use Chronicle\Filament\Support\ComplianceReportStore;
use Chronicle\Filament\Support\ExportArtifactStore;
use Chronicle\Reports\ComplianceReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pre-generates the artifacts the export + reporting surfaces read, so they are
 * not empty on first load: one signed, verifiable export bundle and one signed
 * compliance report, both written to the exports disk via the plugin's own
 * stores (the same path ExportLedgerJob / ComplianceReportJob use, minus the
 * queue and notification). Reads entries and writes artifacts only - no ledger
 * mutation.
 */
class AuditArtifactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @throws Throwable
     */
    public function run(): void
    {
        // Verifiable export of the whole dataset.
        $store = app(ExportArtifactStore::class);
        $workingDir = sys_get_temp_dir().'/chronicle-export-'.Str::uuid();

        try {
            app(ExportManager::class)->export($workingDir);
            $store->store($workingDir);
        } finally {
            $store->deleteLocalDir($workingDir);
        }

        // Signed compliance report covering all entries.
        $tmp = (string) tempnam(sys_get_temp_dir(), 'chronicle-report-');

        try {
            $result = app(ComplianceReport::class)->generate($tmp);
            app(ComplianceReportStore::class)->store($result);
        } finally {
            @unlink($tmp);
        }
    }
}
