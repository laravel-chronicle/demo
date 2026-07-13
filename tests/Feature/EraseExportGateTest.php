<?php

use Chronicle\Filament\ChronicleFilamentPlugin;
use Filament\Facades\Filament;

function auditChroniclePlugin(): ChronicleFilamentPlugin
{
    /** @var ChronicleFilamentPlugin $plugin */
    $plugin = Filament::getPanel('audit')->getPlugin('chronicle-filament');

    return $plugin;
}

it('enables the erase action but never the hold override', function () {
    expect(auditChroniclePlugin()->isErasureEnabled())->toBeTrue()
        ->and(auditChroniclePlugin()->isEraseHoldOverrideAllowed())->toBeFalse();
});

it('authorizes erase and export only for the admin persona', function () {
    session(['demo_persona' => 'admin']);
    expect(auditChroniclePlugin()->canErase())->toBeTrue()
        ->and(auditChroniclePlugin()->canExport())->toBeTrue();
});

it('denies erase and export for non-admin personas and when no persona is set', function () {
    foreach (['physician', 'nurse'] as $persona) {
        session(['demo_persona' => $persona]);
        expect(auditChroniclePlugin()->canErase())->toBeFalse()
            ->and(auditChroniclePlugin()->canExport())->toBeFalse();
    }

    session()->forget('demo_persona');
    expect(auditChroniclePlugin()->canErase())->toBeFalse()
        ->and(auditChroniclePlugin()->canExport())->toBeFalse();
});
