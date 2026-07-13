<div class="mt-4 space-y-4">
    @if ($throttleMessage)
        <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800">
            {{ $throttleMessage }}
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        {{-- Before (valid) --}}
        <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <p class="font-semibold">Before - chain intact</p>
            <p class="mt-1">{{ $baselineChecked }} {{ Str::plural('entry', $baselineChecked) }} verified, hash chain valid.</p>
        </div>

        {{-- After (broken) --}}
        <div class="rounded-md border px-4 py-3 text-sm
            {{ $tampered ? 'border-red-200 bg-red-50 text-red-800' : 'border-gray-200 bg-gray-50 text-gray-500' }}">
            @if ($tampered && ! $valid)
                <p class="font-semibold">After - Verification failed</p>
                <p class="mt-1">{{ $failureReason }}</p>
                <p class="mt-1 font-mono text-xs">Failure type: {{ $failureType }}</p>
                @if ($failedEntryId)
                    <p class="font-mono text-xs">Broken at entry: {{ $failedEntryId }}</p>
                @endif
            @else
                <p class="font-semibold">After</p>
                <p class="mt-1">Pick an entry, then scrub or alter it to see the chain break.</p>
            @endif
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-2"></th>
                    <th class="px-4 py-2">#</th>
                    <th class="px-4 py-2">Action</th>
                    <th class="px-4 py-2">Actor</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($entries as $entry)
                    <tr class="{{ $selectedId === $entry->id ? 'bg-indigo-50' : 'hover:bg-gray-50' }}">
                        <td class="px-4 py-2">
                            <button type="button" wire:click="selectEntry('{{ $entry->id }}')"
                                    class="rounded border px-2 py-0.5 text-xs
                                    {{ $selectedId === $entry->id ? 'border-indigo-500 text-indigo-700' : 'border-gray-300 text-gray-600' }}">
                                {{ $selectedId === $entry->id ? 'Selected' : 'Pick' }}
                            </button>
                        </td>
                        <td class="px-4 py-2 font-mono text-xs text-gray-400">{{ $entry->sequence }}</td>
                        <td class="px-4 py-2 font-mono text-xs text-indigo-700">{{ $entry->action }}</td>
                        <td class="px-4 py-2 text-gray-700">
                            @if ($entry->actor_type === \App\Models\Clinician::class)
                                {{ $actors[$entry->actor_id] ?? 'Unknown clinician' }}
                            @else
                                {{ class_basename($entry->actor_type) }} #{{ $entry->actor_id }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <button type="button" wire:click="scrub" @disabled($selectedId === null)
        class="rounded bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-500 disabled:opacity-40">
            Scrub it (raw DELETE)
        </button>
        <button type="button" wire:click="alter" @disabled($selectedId === null)
        class="rounded bg-orange-600 px-3 py-2 text-sm font-medium text-white hover:bg-orange-500 disabled:opacity-40">
            Alter it (raw UPDATE)
        </button>
        <button type="button" wire:click="restore" @disabled(! $tampered)
        class="rounded border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-40">
            Reset ledger
        </button>
    </div>
</div>
