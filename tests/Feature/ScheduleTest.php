<?php

use Illuminate\Console\Scheduling\Schedule;

it('schedules demo:reset hourly', function () {
    // Running any artisan command boots the console kernel, which loads
    // routes/console.php and registers the scheduled events.
    $this->artisan('schedule:list')->assertSuccessful();

    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'demo:reset'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 * * * *');
});
