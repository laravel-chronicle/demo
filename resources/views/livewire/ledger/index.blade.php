<div>
    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Ledger Explorer</h1>
            <p class="mt-1 text-sm text-gray-600">
                The raw Chronicle ledger — every audited entry, hash-chained in order.
            </p>
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-2">#</th>
                    <th class="px-4 py-2">Action</th>
                    <th class="px-4 py-2">Actor</th>
                    <th class="px-4 py-2">Subject</th>
                    <th class="px-4 py-2">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($entries as $entry)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono text-xs text-gray-400">{{ $entry->sequence }}</td>
                        <td class="px-4 py-2 font-mono text-xs text-indigo-700">{{ $entry->action }}</td>
                        <td class="px-4 py-2 text-gray-700">
                            @if ($entry->actor_type === \App\Models\Clinician::class)
                                {{ $actors[$entry->actor_id] ?? 'Unknown clinician' }}
                            @else
                                <span class="text-gray-500">{{ class_basename($entry->actor_type) }} #{{ $entry->actor_id }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-gray-500">
                            {{ class_basename($entry->subject_type) }} #{{ $entry->subject_id }}
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-400">{{ $entry->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">
                            The ledger is empty. Run <code>php artisan migrate:fresh --seed</code>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $entries->links() }}
    </div>
</div>
