<x-layouts.app title="MedLedger - Chronicle Demo">
    <section class="max-w-3xl">
        <h1 class="text-3xl font-bold tracking-tight">MedLedger</h1>
        <p class="mt-4 text-lg leading-relaxed text-gray-700">
            MedLedger is a fictional clinic that shows off
            <a href="{{ config('demo.links.docs') }}" class="font-medium text-indigo-600 hover:underline">Laravel Chronicle</a> -
            a tamper-evident audit ledger for Laravel. Watch audit trails appear automatically as records
            are viewed and changed, then see the ledger detect tampering, survive a key rotation, and fail a
            forged trail under external anchoring. Chronicle guarantees the integrity of the audit ledger
            itself: it catches anyone who edits, deletes, or reorders audit entries to cover their tracks.
        </p>

        <div class="mt-8 flex gap-3">
            <a href="{{ route('patients.index') }}"
               class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                Explore the demo
            </a>
            <a href="{{ config('demo.links.repo') }}"
               class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-100">
                View the repo
            </a>
            <a href="{{ config('demo.links.docs') }}"
               class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-100">
                Read the docs
            </a>
        </div>
    </section>
</x-layouts.app>
