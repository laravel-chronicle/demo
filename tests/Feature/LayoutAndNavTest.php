<?php

beforeEach(fn () => $this->withoutVite());

it('renders every nav screen as a reachable stub', function () {
    foreach (['patients.index', 'ledger.index', 'lab.index', 'auditors.index', 'how.it.works'] as $name) {
        $this->get(route($name))->assertOk();
    }
});

it('shows the fictional-data banner and the active persona on a stub page', function () {
    $this->get(route('patients.index'))
        ->assertOk()
        ->assertSee('all data is fictional')
        ->assertSee('Dr. Reyes');
});

it('reflects a switched persona in the layout', function () {
    $this->withSession(['demo_persona' => 'admin'])
        ->get(route('patients.index'))
        ->assertOk()
        ->assertSee('Admin Vega');
});
