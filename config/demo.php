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

    /**
     * Matomo analytics. Both values are blank unless set in the environment, so
     * tracking is off in local/dev/CI and only activates on the VPS where the
     * self-hosted Matomo instance and site ID are configured.
     *
     * @var array{matomo_url: ?string, matomo_site_id: ?string}
     */
    'analytics' => [
        'matomo_url' => env('MATOMO_URL'),
        'matomo_site_id' => env('MATOMO_SITE_ID'),
    ],
];
