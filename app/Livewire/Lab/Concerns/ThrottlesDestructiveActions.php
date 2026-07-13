<?php

namespace App\Livewire\Lab\Concerns;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Lightweight per-IP throttle for destructive Lab actions on a public URL.
 *
 * Session 5 adds the global demo throttling; this keeps individual panels from
 * being hammered in the meantime.
 */
trait ThrottlesDestructiveActions
{
    /** User-facing message shown when an action is throttled. */
    public ?string $throttleMessage = null;

    /**
     * Returns true when the action may proceed. When the caller is over the
     * limit it sets $this->throttleMessage and returns false.
     */
    protected function passesThrottle(string $action, int $maxAttempts = 10, int $decaySeconds = 60): bool
    {
        $key = 'lab:'.$action.':'.(request()->ip() ?? 'unknown');

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $this->throttleMessage = 'Too many attempts - please wait a moment and try again.';

            return false;
        }

        RateLimiter::hit($key, $decaySeconds);
        $this->throttleMessage = null;

        return true;
    }
}
