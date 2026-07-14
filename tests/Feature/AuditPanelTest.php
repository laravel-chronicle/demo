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

it('registers the chronicle plugin on the audit panel', function () {
    $panel = Filament::getPanel('audit');

    // Substitute the real plugin id string from the discovered plugin class::getId().
    expect($panel->hasPlugin('chronicle-filament'))->toBeTrue();
});

it('redirects the For Auditors front door to the audit panel', function () {
    $this->get(route('auditors.index'))->assertRedirect('/audit');
});
