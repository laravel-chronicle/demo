<?php

use App\Livewire\Lab\AuditorView;
use App\Livewire\Lab\FullCompromise;
use App\Livewire\Lab\KeyRotation;
use App\Livewire\Lab\LifecycleStepper;
use App\Livewire\Lab\TamperSimulator;
use App\Livewire\ResetDemo;

/**
 * A public Livewire property and an action method that share a name collide on
 * Livewire's `$wire` JS proxy: its `get` trap resolves synced state (properties)
 * before the method-call fallback, so the property value shadows the method and
 * `wire:click="name"` silently does nothing in the browser. Server-side
 * `->call('name')` bypasses the proxy and still works, which hides the bug from
 * ordinary component tests - hence this structural guard.
 */
it('has no public property that shadows an action method on the $wire proxy', function (string $component) {
    $reflection = new ReflectionClass($component);

    $properties = array_map(
        fn (ReflectionProperty $property) => $property->getName(),
        $reflection->getProperties(ReflectionProperty::IS_PUBLIC),
    );

    $methods = array_values(array_filter(
        array_map(
            fn (ReflectionMethod $method) => $method->getName(),
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
        ),
        fn (string $name) => (new ReflectionMethod($component, $name))->getDeclaringClass()->getName() === $component,
    ));

    expect(array_values(array_intersect($properties, $methods)))->toBe([]);
})->with([
    TamperSimulator::class,
    LifecycleStepper::class,
    AuditorView::class,
    KeyRotation::class,
    FullCompromise::class,
    ResetDemo::class,
]);
