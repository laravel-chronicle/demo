<?php

it('defaults to the Dr. Reyes physician persona', function () {
    expect(config('demo.default_persona'))->toBe('physician')
        ->and(config('demo.personas.physician.name'))->toBe('Dr. Reyes');
});

it('switches the active persona and stores it in the session', function () {
    $this->post(route('persona.switch'), ['persona' => 'nurse'])
        ->assertRedirect();

    expect(session('demo_persona'))->toBe('nurse');
});

it('rejects an unknown persona', function () {
    $this->from('/')
        ->post(route('persona.switch'), ['persona' => 'hacker'])
        ->assertSessionHasErrors('persona');
});
