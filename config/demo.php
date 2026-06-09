<?php

return [
    'default_persona' => 'physician',

    /**
     * Synthetic, no-auth personas used by the demo role switcher.
     *
     * @var array<string, array{name: string, role: string}>
     */
    'personas' => [
        'physician' => ['name' => 'Dr. Reyes', 'role' => 'physician'],
        'nurse' => ['name' => 'Nurse Okoro', 'role' => 'nurse'],
        'admin' => ['name' => 'Admin Vega', 'role' => 'admin'],
    ],

    'links' => [
        'repo' => 'https://github.com/laravel-chronicle/core',
        'docs' => 'https://laravel-chronicle.github.io/docs/overview',
    ],
];
