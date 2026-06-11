<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PersonaController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'persona' => ['required', 'string', Rule::in(array_keys((array) config('demo.personas')))],
        ]);

        $request->session()->put('demo_persona', $request->string('persona')->toString());

        return back();
    }
}
