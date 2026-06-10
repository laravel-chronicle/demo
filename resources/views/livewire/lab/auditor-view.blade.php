<div class="mt-4 space-y-4">
    <div class="flex flex-wrap items-end gap-3">
        <label class="text-sm">
            <span class="block text-gray-600">From</span>
            <input type="date" wire:model="from" class="mt-1 rounded border border-gray-300 px-2 py-1 text-sm">
        </label>
        <label class="text-sm">
            <span class="block text-gray-600">To</span>
            <input type="date" wire:model="to" class="mt-1 rounded border border-gray-300 px-2 py-1 text-sm">
        </label>
        <button type="button" wire:click="generateReport"
                class="rounded bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500">
            Generate signed report
        </button>
        <button type="button" wire:click="buildExport"
                class="rounded bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500">
            Build export bundle
        </button>
        <button type="button" wire:click="restore"
                class="rounded border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Reset
        </button>
    </div>

    @if ($report)
        <div class="rounded border border-gray-200 bg-gray-50 px-4 py-3 text-sm">
            <div class="flex items-center justify-between">
                <p class="font-semibold text-gray-700">Compliance report</p>
                <button type="button" wire:click="downloadReport" class="text-xs text-indigo-600 hover:underline">
                    Download HTML
                </button>
            </div>
            <dl class="mt-2 grid gap-x-6 gap-y-1 font-mono text-xs text-gray-600 sm:grid-cols-2">
                <div><dt class="inline text-gray-400">entries:</dt> {{ $report['entry_count'] }}</div>
                <div><dt class="inline text-gray-400">key:</dt> {{ $report['key_id'] }} ({{ $report['algorithm'] }})</div>
                <div class="sm:col-span-2"><dt class="inline text-gray-400">chain head:</dt> {{ Str::limit($report['chain_head'] ?? '—', 40) }}</div>
                <div class="sm:col-span-2"><dt class="inline text-gray-400">report hash:</dt> {{ Str::limit($report['report_hash'], 40) }}</div>
                <div class="sm:col-span-2"><dt class="inline text-gray-400">signature:</dt> {{ Str::limit($report['signature'], 40) }}</div>
            </dl>
            @if ($reportValid)
                <p class="mt-2 text-sm font-medium text-green-700">✓ Report signature valid</p>
            @elseif ($reportValid !== null)
                <p class="mt-2 text-sm font-medium text-red-700">✗ Report signature invalid</p>
            @endif
        </div>
    @endif

    @if ($export)
        <div class="rounded border border-gray-200 bg-gray-50 px-4 py-3 text-sm">
            <p class="font-semibold text-gray-700">Export bundle</p>
            <dl class="mt-2 space-y-0.5 font-mono text-xs text-gray-600">
                <div><dt class="inline text-gray-400">entries:</dt> {{ $export['entry_count'] }}</div>
                <div><dt class="inline text-gray-400">dataset hash:</dt> {{ Str::limit($export['dataset_hash'], 40) }}</div>
            </dl>
            <div class="mt-2 flex items-center gap-3">
                <button type="button" wire:click="verifyExport" class="rounded border border-gray-300 px-2 py-1 text-xs hover:bg-gray-100">
                    Verify export
                </button>
                @if ($exportValid)
                    <span class="text-sm font-medium text-green-700">✓ Export verified</span>
                @elseif ($exportValid !== null)
                    <span class="text-sm font-medium text-red-700">✗ Export verification failed</span>
                @endif
            </div>
        </div>
    @endif
</div>
