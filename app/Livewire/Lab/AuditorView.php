<?php

namespace App\Livewire\Lab;

use App\Support\LabSandbox;
use Carbon\Carbon;
use Chronicle\Exports\ExportManager;
use Chronicle\Reports\ComplianceReport;
use Chronicle\Verification\ExportVerifier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use JsonException;
use Livewire\Component;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AuditorView extends Component
{
    public string $from = '';

    public string $to = '';

    /** @var array<string, mixed>|null */
    public ?array $report = null;

    /** @var array<string, mixed>|null */
    public ?array $export = null;

    public ?string $reportPath = null;

    public ?string $exportPath = null;

    public ?bool $reportValid = null;

    public ?bool $exportValid = null;

    public function mount(): void
    {
        $this->from = Carbon::now()->subDays(30)->toDateString();
        $this->to = Carbon::now()->toDateString();
    }

    public function generateReport(ComplianceReport $report): void
    {
        $dir = $this->ensureDir();
        $path = $dir.'/report-'.Str::ulid().'.html';

        $result = $report->generate(
            path: $path,
            from: Carbon::parse($this->from)->startOfDay(),
            to: Carbon::parse($this->to)->endOfDay(),
        );

        $this->reportPath = $path;
        $this->report = [
            'entry_count' => $result->entryCount,
            'chain_head' => $result->chainHead,
            'report_hash' => $result->reportHash,
            'signature' => $result->signature,
            'algorithm' => $result->algorithm,
            'key_id' => $result->keyId,
            'generated_at' => $result->generatedAt->toIso8601String(),
        ];

        $this->reportValid = $report->verify(
            $result->reportHash,
            $result->signature,
            $result->algorithm,
            $result->keyId,
        );
    }

    public function buildExport(ExportManager $manager): void
    {
        $dir = $this->ensureDir();
        $path = $dir.'/auditor-export-'.Str::ulid().'.json';

        $result = $manager->export($path);

        $this->exportPath = $path;
        $this->export = [
            'entry_count' => $result->entryCount,
            'dataset_hash' => $result->datasetHash,
            'chain_head' => $result->chainHead,
        ];
        $this->exportValid = null;
    }

    /**
     * @throws JsonException
     */
    public function verifyExport(ExportVerifier $verifier): void
    {
        if ($this->exportPath === null) {
            return;
        }

        $this->exportValid = $verifier->verify($this->exportPath)->isValid();
    }

    public function downloadReport(): ?BinaryFileResponse
    {
        if ($this->reportPath === null || ! is_file($this->reportPath)) {
            return null;
        }

        return response()->download($this->reportPath, 'compliance-report.html');
    }

    public function restore(LabSandbox $sandbox): void
    {
        $sandbox->deletePath($this->reportPath);
        $sandbox->deletePath($this->exportPath);

        $this->reset('report', 'export', 'reportPath', 'exportPath', 'reportValid', 'exportValid');
        $this->mount();
    }

    private function ensureDir(): string
    {
        $dir = storage_path('app/lab');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    public function render(): View
    {
        return view('livewire.lab.auditor-view');
    }
}
