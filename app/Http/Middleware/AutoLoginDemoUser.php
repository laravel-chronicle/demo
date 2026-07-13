<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs in a single read-only demo user so the audit panel is browsable
 * without a login wall (decision 1). No persona logic - the demo persona
 * gate lives in the plugin authorizers added in M3.
 */
class AutoLoginDemoUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            $user = User::query()->firstOrCreate(
                ['email' => DemoUserSeeder::DEMO_EMAIL],
                ['name' => 'Demo Auditor', 'password' => 'password'],
            );

            Auth::login($user);
        }

        return $next($request);
    }
}
