<?php

use Database\Seeders\DemoUserSeeder;
use Filament\Facades\Filament;

it('registers the audit panel', function () {
    $panel = Filament::getPanel('audit');

    expect($panel)->not->toBeNull()
        ->and($panel->getPath())->toBe('audit');
});

it('lets anyone browse the audit panel without logging in', function () {
    $this->seed(DemoUserSeeder::class);

    $response = $this->get('/audit');

    $response->assertOk();
    expect(auth()->check())->toBeTrue();
});
