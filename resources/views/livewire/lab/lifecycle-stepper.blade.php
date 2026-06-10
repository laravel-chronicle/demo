<div class="mt-4 space-y-4">
    @if ($throttleMessage)
        <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800">
            {{ $throttleMessage }}
        </div>
    @endif

    <div class="flex flex-wrap items-center gap-2">
        <button type="button" wire:click="generateActivity"
                class="rounded bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500">
            1 · Generate activity
        </button>
        <button type="button" wire:click="createCheckpoint" @disabled($step < 1)
        class="rounded bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-40">
            2 · Create checkpoint
        </button>
        <button type="button" wire:click="anchor" @disabled($step < 2)
        class="rounded bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-40">
            3 · Anchor
        </button>
        <button type="button" wire:click="export" @disabled($step < 3)
        class="rounded bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-40">
            4 · Export
        </button>
        <button type="button" wire:click="verifyExport" @disabled($step < 4)
        class="rounded bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-40">
            5 · Verify export
        </button>
        <button type="button" wire:click="restore" @disabled($step === 0)
        class="rounded border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-40">
            Reset
        </button>
    </div>

    <dl class="grid gap-3 text-sm md:grid-cols-2">
        @if ($generatedEntries)
            <div class="rounded border border-gray-200 bg-gray-50 px-4 py-3">
                <dt class="font-semibold text-gray-700">Activity generated</dt>
                <dd class="mt-1 text-gray-600">{{ $generatedEntries }} new ledger {{ Str::plural('entry', $generatedEntries) }}.</dd>
            </div>
        @endif

        @if ($checkpoint)
            <div class="rounded border border-gray-200 bg-gray-50 px-4 py-3">
                <dt class="font-semibold text-gray-700">Checkpoint (signed chain head)</dt>
                <dd class="mt-1 space-y-0.5 font-mono text-xs text-gray-600">
                    <p>id: {{ $checkpoint['id'] }}</p>
                    <p>chain head: {{ Str::limit($checkpoint['chain_hash'], 24) }}</p>
                    <p>key: {{ $checkpoint['key_id'] }} ({{ $checkpoint['algorithm'] }})</p>
                    <p>entries: {{ $checkpoint['entry_count'] }}</p>
                </dd>
            </div>
        @endif

        @if ($anchor)
            <div class="rounded border border-gray-200 bg-gray-50 px-4 py-3">
                <dt class="font-semibold text-gray-700">
                    Anchor receipt
                    <span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium uppercase text-amber-800">non-production · NullAnchor</span>
                </dt>
                <dd class="mt-1 space-y-0.5 font-mono text-xs text-gray-600">
                    <p>provider: {{ $anchor['provider'] }}</p>
                    <p>proof: {{ Str::limit($anchor['proof'], 24) }}</p>
                    <p>at: {{ $anchor['anchored_at'] }}</p>
                </dd>
            </div>
        @endif

        @if ($export)
            <div class="rounded border border-gray-200 bg-gray-50 px-4 py-3">
                <dt class="font-semibold text-gray-700">Export manifest</dt>
                <dd class="mt-1 space-y-0.5 font-mono text-xs text-gray-600">
                    <p>entries: {{ $export['entry_count'] }}</p>
                    <p>dataset hash: {{ Str::limit($export['dataset_hash'], 24) }}</p>
                    <p>chain head: {{ Str::limit($export['chain_head'] ?? '—', 24) }}</p>
                </dd>
            </div>
        @endif
    </dl>

    @if ($step >= 5)
        <div class="rounded-md border px-4 py-3 text-sm
            {{ $exportVerified ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800' }}">
            {{ $exportVerified ? '✓ Verified — the export reproduces the ledger and its signature checks out.' : '✗ Export verification failed.' }}
        </div>
    @endif
</div>
