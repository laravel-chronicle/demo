<div class="space-y-10">
    <header>
        <h1 class="text-2xl font-semibold">Integrity Lab</h1>
        <p class="mt-1 max-w-3xl text-sm text-gray-600">
            Four hands-on demonstrations of what Chronicle's hash-chained ledger actually
            guarantees. Each panel explains what it is about to do, runs it against this demo's
            real ledger, shows the artifacts, and offers a one-click reset.
        </p>
        <p class="mt-2 max-w-3xl text-sm text-gray-500">
            Everything here is synthetic and reversible. Tamper attacks use raw database writes
            that bypass Chronicle — exactly how a real attacker with database access would work.
        </p>
    </header>

    <section id="panel-4a" class="rounded-xl border border-gray-200 bg-white p-6">
        <h2 class="text-lg font-semibold text-gray-900">4a · Tampering simulator</h2>
        <p class="mt-1 text-sm text-gray-600">
            Scrub or alter a real audit entry behind Chronicle's back, then watch Verify localize
            the break to the exact entry with the reason.
        </p>
        <livewire:lab.tamper-simulator />
    </section>

    <section id="panel-4b" class="rounded-xl border border-gray-200 bg-white p-6">
        <h2 class="text-lg font-semibold text-gray-900">4b · Full lifecycle</h2>
        <p class="mt-1 text-sm text-gray-600">
            Step through generate activity → checkpoint → anchor → export → verify-export, with the
            signed artifact rendered at every step.
        </p>
        <livewire:lab.lifecycle-stepper />
    </section>

    <section id="panel-4c" class="rounded-xl border border-gray-200 bg-white p-6">
        <h2 class="text-lg font-semibold text-gray-900">4c · Auditor view</h2>
        <p class="mt-1 text-sm text-gray-600">
            Generate a signed compliance report for a date range plus an export bundle, then verify
            both independently. The "hand this to an auditor" story.
        </p>
        <livewire:lab.auditor-view />
    </section>

    <section id="panel-4d" class="rounded-xl border border-gray-200 bg-white p-6">
        <h2 class="text-lg font-semibold text-gray-900">4d · Key rotation</h2>
        <p class="mt-1 text-sm text-gray-600">
            Rotate to a second signing key and confirm pre-rotation checkpoints still verify under
            the retired key while new checkpoints use the new one.
        </p>
        <livewire:lab.key-rotation />
    </section>
</div>
