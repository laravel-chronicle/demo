<?php

namespace App\Support;

use App\Models\Clinician;
use Illuminate\Support\Facades\Config;

class CurrentClinician
{
    /**
     * Resolve the Clinician backing the active demo persona.
     *
     * Falls back to the default persona when the session value is missing
     * or does not match a configured persona.
     */
    public function get(): Clinician
    {
        $personas = Config::array('demo.personas');
        $default = Config::string('demo.default_persona');

        $session = session('demo_persona');
        $key = is_string($session) ? $session : $default;

        if (! array_key_exists($key, $personas)) {
            $key = $default;
        }

        return Clinician::query()->where('persona_key', $key)->firstOrFail();
    }
}
