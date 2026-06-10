<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Psr\SimpleCache\InvalidArgumentException;

class ResetDemo extends Component
{
    /** Per-IP cap on manual resets within the decay window. */
    private const int MAX_RESETS = 3;

    /** Throttle window in seconds (one hour). */
    private const int DECAY_SECONDS = 3600;

    /** User-facing message shown when a reset is throttled. */
    public ?string $message = null;

    /**
     * Rebuild the whole demo via `demo:reset`, then redirect home for a clean,
     * fully-rebuilt page. Throttled per IP using the FILE cache store, because
     * demo:reset runs migrate:fresh which drops the database-backed cache table
     * (the default store) and would otherwise wipe its own throttle.
     *
     * @throws InvalidArgumentException
     */
    public function resetDemo(): void
    {
        $store = Cache::store('file');
        $key = 'demo-reset:'.(request()->ip() ?? 'unknown');
        $attempts = $store->get($key, 0);
        $attempts = is_int($attempts) ? $attempts : 0;

        if ($attempts >= self::MAX_RESETS) {
            $this->message = 'The demo was reset recently — please wait before resetting again.';

            return;
        }

        // Record the attempt before rebuilding so the throttle survives the wipe.
        $store->put($key, $attempts + 1, now()->addSeconds(self::DECAY_SECONDS));

        Artisan::call('demo:reset');

        $this->redirect(route('home'));
    }

    public function render(): View
    {
        return view('livewire.reset-demo');
    }
}
