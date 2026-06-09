<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveDemoPersona
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var array<string, array{name: string, role: string}> $personas */
        $personas = (array) config('demo.personas');

        /** @var string $default */
        $default = config('demo.default_persona');
        /** @var string $key */
        $key = $request->session()->get('demo_persona', $default);

        if (! array_key_exists($key, $personas)) {
            $key = $default;
        }

        View::share('personas', $personas);
        View::share('currentPersonaKey', $key);
        View::share('currentPersona', $personas[$key]);

        return $next($request);
    }
}
