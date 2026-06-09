<?php

beforeEach(fn () => $this->withoutVite());

it('renders the home pitch with repo and docs links', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('tamper-evident')
        ->assertSee(config('demo.links.repo'))
        ->assertSee(config('demo.links.docs'));
});

it('shows the active persona in the layout on the home page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Dr. Reyes');
});
