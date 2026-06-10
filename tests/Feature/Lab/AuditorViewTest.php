<?php

use App\Livewire\Lab\AuditorView;
use App\Models\Patient;
use Database\Seeders\ClinicianSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(ClinicianSeeder::class);
    Patient::factory()->count(3)->create();
});

it('generates a signed compliance report and verifies its signature', function () {
    $component = Livewire::test(AuditorView::class)
        ->call('generateReport')
        ->assertSet('reportValid', true)
        ->assertSee('Report signature valid');

    $report = $component->get('report');
    expect($report)->not->toBeNull()
        ->and($report['entry_count'])->toBeGreaterThan(0)
        ->and($report['signature'])->not->toBeEmpty()
        ->and(is_file($component->get('reportPath')))->toBeTrue();
});

it('builds an export bundle and shows an independent verify badge', function () {
    $component = Livewire::test(AuditorView::class)
        ->call('buildExport')
        ->call('verifyExport')
        ->assertSet('exportValid', true)
        ->assertSee('Export verified');

    expect($component->get('export'))->not->toBeNull();
});

it('clears generated files on reset', function () {
    $component = Livewire::test(AuditorView::class)
        ->call('generateReport')
        ->call('buildExport');

    $reportPath = $component->get('reportPath');
    $exportPath = $component->get('exportPath');

    $component->call('restore')
        ->assertSet('report', null)
        ->assertSet('export', null);

    expect(is_file($reportPath))->toBeFalse()
        ->and(is_dir($exportPath))->toBeFalse();
});
