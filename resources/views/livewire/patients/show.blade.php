<div class="grid gap-8 lg:grid-cols-3">
    <section class="lg:col-span-2">
        <a href="{{ route('patients.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; All patients</a>

        @if (session('status'))
            <div class="mt-4 rounded-md bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <h1 class="mt-3 text-2xl font-semibold">{{ $patient->name }}</h1>
        <dl class="mt-4 grid grid-cols-2 gap-4 rounded-lg border border-gray-200 bg-white p-4 text-sm">
            <div><dt class="text-gray-500">MRN</dt><dd class="font-medium">{{ $patient->mrn }}</dd></div>
            <div><dt class="text-gray-500">Date of birth</dt><dd class="font-medium">{{ $patient->dob->toFormattedDateString() }}</dd></div>
            <div><dt class="text-gray-500">Allergies</dt><dd class="font-medium">{{ $patient->allergies ?: '—' }}</dd></div>
            <div class="col-span-2"><dt class="text-gray-500">Notes</dt><dd class="font-medium">{{ $patient->notes ?: '—' }}</dd></div>
        </dl>
    </section>

    {{-- Live audit trail --}}
    <aside class="lg:col-span-1">
        <h2 class="text-lg font-semibold">Audit trail</h2>
        <p class="mt-1 text-xs text-gray-500">Pulled live from the Chronicle ledger.</p>

        <ol class="mt-4 space-y-3">
            @forelse ($trail as $entry)
                <li class="rounded-md border border-gray-200 bg-white p-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-xs text-indigo-700">{{ $entry->action }}</span>
                        <time class="text-xs text-gray-400">{{ $entry->created_at->diffForHumans() }}</time>
                    </div>
                    <p class="mt-1 text-xs text-gray-600">
                        by {{ $entry->actor_type === \App\Models\Clinician::class ? ($clinicians[$entry->actor_id] ?? 'Unknown') : $entry->actor_id }}
                    </p>
                    @if ($entry->diff)
                        <ul class="mt-2 space-y-1 text-xs">
                            @foreach ($entry->diff as $field => $change)
                                <li class="text-gray-700">
                                    <span class="font-medium">{{ $field }}:</span>
                                    <span class="text-red-600 line-through">{{ $change['old'] ?? '—' }}</span>
                                    &rarr;
                                    <span class="text-green-700">{{ $change['new'] ?? '—' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @empty
                <li class="text-sm text-gray-500">No audit entries yet.</li>
            @endforelse
        </ol>
    </aside>
</div>
