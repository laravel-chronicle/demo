<?php

use App\Models\Patient;
use App\Support\LabSandbox;
use Chronicle\Checkpoints\CheckpointCreator;
use Database\Seeders\ClinicianSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(ClinicianSeeder::class);
    Patient::factory()->count(2)->create();
});

it('deletes a checkpoint and un-stamps its entries', function () {
    $checkpoint = app(CheckpointCreator::class)->create();

    expect(DB::table('chronicle_entries')->where('checkpoint_id', $checkpoint->id)->count())
        ->toBeGreaterThan(0);

    app(LabSandbox::class)->forgetCheckpoints([$checkpoint->id]);

    expect(DB::table('chronicle_checkpoints')->where('id', $checkpoint->id)->exists())->toBeFalse()
        ->and(DB::table('chronicle_entries')->where('checkpoint_id', $checkpoint->id)->count())->toBe(0);
});

it('deletes a file if it exists and ignores a missing one', function () {
    $path = storage_path('app/lab/sandbox-test.txt');
    @mkdir(dirname($path), 0775, true);
    file_put_contents($path, 'x');

    app(LabSandbox::class)->deletePath($path);
    app(LabSandbox::class)->deletePath($path); // second call is a no-op

    expect(file_exists($path))->toBeFalse();
});

it('deletes a directory bundle and its contents', function () {
    $dir = storage_path('app/lab/sandbox-bundle');
    @mkdir($dir, 0775, true);
    file_put_contents($dir.'/entries.ndjson', '{}');
    file_put_contents($dir.'/manifest.json', '{}');

    app(LabSandbox::class)->deletePath($dir);

    expect(is_dir($dir))->toBeFalse();
});
