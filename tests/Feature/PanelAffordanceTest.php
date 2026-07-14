<?php

use Database\Seeders\DemoUserSeeder;

beforeEach(function () {
    $this->seed(DemoUserSeeder::class);
});

it('always shows the resets-hourly honesty note in the panel', function () {
    $this->get('/audit')->assertOk()->assertSee('resets hourly');
});

it('prompts non-admin visitors to switch to Admin Vega to unlock erase and export', function () {
    $this->withSession(['demo_persona' => 'nurse'])
        ->get('/audit')
        ->assertOk()
        ->assertSee('Switch to the Admin Vega persona');
});

it('confirms the erase and export actions are unlocked for the admin persona', function () {
    $this->withSession(['demo_persona' => 'admin'])
        ->get('/audit')
        ->assertOk()
        ->assertSee('GDPR erase and export actions are unlocked');
});
