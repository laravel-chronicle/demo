<?php

use Filament\Facades\Filament;

it('registers the audit panel', function () {
    $panel = Filament::getPanel('audit');

    expect($panel)->not->toBeNull()
        ->and($panel->getPath())->toBe('audit');
});
