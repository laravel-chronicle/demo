<?php

use App\Livewire\Lab\LifecycleStepper;
use App\Models\Patient;
use Chronicle\Checkpoints\Checkpoint;
use Database\Seeders\ClinicianSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(ClinicianSeeder::class);
    Patient::factory()->count(2)->create();
});

it('steps through checkpoint, anchor, export and verify-export with artifacts', function () {
    $component = Livewire::test(LifecycleStepper::class)
        ->call('generateActivity')
        ->assertSet('step', 1)
        ->call('createCheckpoint')
        ->assertSet('step', 2)
        ->call('anchor')
        ->assertSet('step', 3)
        ->call('export')
        ->assertSet('step', 4)
        ->call('verifyExport')
        ->assertSet('step', 5)
        ->assertSet('exportVerified', true)
        ->assertSee('Verified');

    expect($component->get('checkpoint'))->not->toBeNull()
        ->and($component->get('anchor'))->not->toBeNull()
        ->and($component->get('export'))->not->toBeNull();

    $checkpointId = $component->get('createdCheckpointId');
    expect(Checkpoint::query()->whereKey($checkpointId)->exists())->toBeTrue();
});

it('removes its checkpoint and export file on reset', function () {
    $component = Livewire::test(LifecycleStepper::class)
        ->call('generateActivity')
        ->call('createCheckpoint')
        ->call('export');

    $checkpointId = $component->get('createdCheckpointId');
    $exportPath = $component->get('exportPath');
    expect(is_dir($exportPath))->toBeTrue();

    $component->call('restore')->assertSet('step', 0);

    expect(DB::table('chronicle_checkpoints')->where('id', $checkpointId)->exists())->toBeFalse()
        ->and(is_dir($exportPath))->toBeFalse();
});
