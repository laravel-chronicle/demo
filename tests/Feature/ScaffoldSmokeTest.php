<?php

beforeEach(fn () => $this->withoutVite());

it('boots and renders every public screen without error', function () {
    $routes = ['home', 'patients.index', 'ledger.index', 'lab.index', 'auditors.index', 'how.it.works'];

    foreach ($routes as $name) {
        $this->get(route($name))
            ->assertOk()
            ->assertSee('all data is fictional')
            ->assertSee('MedLedger');
    }
});

it('exposes a health check', function () {
    $this->get('/up')->assertOk();
});
