<?php

use Illuminate\Support\Facades\File;

it('ships a Dockerfile that builds assets and installs production-only dependencies', function () {
    $dockerfile = File::get(base_path('Dockerfile'));

    expect($dockerfile)
        ->toContain('npm run build')
        ->toContain('--no-dev')
        ->toContain('frankenphp');
});

it('runs the scheduler alongside the web server in the container', function () {
    $supervisor = File::get(base_path('docker/supervisord.conf'));

    expect($supervisor)
        ->toContain('schedule:work')
        ->toContain('frankenphp');
});

it('seeds the demo dataset on first boot in the entrypoint', function () {
    $entrypoint = File::get(base_path('docker/entrypoint.sh'));

    expect($entrypoint)
        ->toContain('demo:reset')
        ->toContain('config:cache');
});

it('keeps secrets and local artifacts out of the Docker build context', function () {
    $ignore = File::get(base_path('.dockerignore'));

    expect($ignore)
        ->toContain('.env')
        ->toContain('.git')
        ->toContain('node_modules')
        ->toContain('vendor')
        ->toContain('database/database.sqlite');
});

it('configures Fly to persist SQLite on a volume and health-check the app', function () {
    $fly = File::get(base_path('fly.toml'));

    expect($fly)
        ->toContain('destination = "/data"')
        ->toContain('DB_DATABASE = "/data/database.sqlite"')
        ->toContain('internal_port = 8080')
        ->toContain('path = "/up"')
        ->toContain('min_machines_running = 1');
});
