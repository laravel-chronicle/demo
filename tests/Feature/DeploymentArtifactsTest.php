<?php

use Illuminate\Support\Facades\File;

it('ships a Dockerfile that builds assets and installs production-only dependencies', function () {
    $dockerfile = File::get(base_path('Dockerfile'));

    expect($dockerfile)
        ->toContain('npm run build')
        ->toContain('--no-dev')
        ->toContain('frankenphp');
});

it('keeps Faker in production dependencies because demo:reset seeds at boot', function () {
    $composer = json_decode(File::get(base_path('composer.json')), true);

    expect($composer['require'])->toHaveKey('fakerphp/faker')
        ->and($composer['require-dev'] ?? [])->not->toHaveKey('fakerphp/faker');
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
        ->toContain('config:cache')
        ->toContain('migrate --force');
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
        ->toContain("destination = '/data'")
        ->toContain("DB_DATABASE = '/data/database.sqlite'")
        ->toContain('internal_port = 8080')
        ->toContain("path = '/up'")
        ->toContain('min_machines_running = 1');
});

it('documents the required production secrets and steps in DEPLOY.md', function () {
    $deploy = File::get(base_path('DEPLOY.md'));

    expect($deploy)
        ->toContain('APP_KEY')
        ->toContain('chronicle:key:generate')
        ->toContain('CHRONICLE_TSA_URL')
        ->toContain('fly volumes create')
        ->toContain('fly deploy')
        ->toContain('Forge');
});

it('explains the local run and links the docs in the README', function () {
    $readme = File::get(base_path('README.md'));

    expect($readme)
        ->toContain('composer install')
        ->toContain('demo:reset')
        ->toContain('artisan serve')
        ->toContain('Add a showcase')
        ->toContain(config('demo.links.docs'));
});
