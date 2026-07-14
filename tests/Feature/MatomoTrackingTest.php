<?php

use function Pest\Laravel\get;

it('injects the cookieless Matomo tracker on public pages when configured', function () {
    config()->set('demo.analytics.matomo_url', 'https://analytics.laravel-chronicle.dev');
    config()->set('demo.analytics.matomo_site_id', '7');

    get(route('home'))
        ->assertSuccessful()
        ->assertSee('analytics.laravel-chronicle.dev', false)
        ->assertSee('matomo.js', false)
        ->assertSee("_paq.push(['setSiteId', '7']", false)
        ->assertSee("_paq.push(['disableCookies'])", false)
        ->assertSee("_paq.push(['setDoNotTrack', true])", false);
});

it('omits the Matomo tracker on public pages when not configured', function () {
    config()->set('demo.analytics.matomo_url', null);
    config()->set('demo.analytics.matomo_site_id', null);

    get(route('home'))
        ->assertSuccessful()
        ->assertDontSee('matomo.js', false)
        ->assertDontSee('_paq', false);
});

it('injects the Matomo tracker into the Filament audit panel head when configured', function () {
    config()->set('demo.analytics.matomo_url', 'https://analytics.laravel-chronicle.dev');
    config()->set('demo.analytics.matomo_site_id', '7');

    get('/audit')
        ->assertSuccessful()
        ->assertSee('analytics.laravel-chronicle.dev', false)
        ->assertSee('matomo.js', false);
});
