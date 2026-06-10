<div class="mt-4 space-y-4">
    @if (! $configured)
        <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            <p class="font-semibold">Configure a TSA to see this demo</p>
            <p class="mt-1">
                The full-compromise demo requires a <strong>real external anchor</strong>. Set
                <code class="font-mono text-xs">CHRONICLE_TSA_URL</code> (e.g.
                <code class="font-mono text-xs">https://freetsa.org/tsr</code>) and ship the TSA CA at
                <code class="font-mono text-xs">storage/tsa/cacert.pem</code>, then reload. We never fake a pass:
                an in-database "anchor" the attacker controls would falsely succeed, so without a TSA this panel
                stays inert.
            </p>
        </div>
    @else
        @if ($throttleMessage)
            <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800">
                {{ $throttleMessage }}
            </div>
        @endif

        <p class="text-sm text-gray-600">
            An attacker with <strong>database and signing-key access</strong> rewrites the ledger and re-signs
            every checkpoint. Offline verification can't catch them — but the external timestamp can, because the
            TSA already signed the <em>original</em> digest and the attacker can't re-issue that.
        </p>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="buildAnchoredLedger" @disabled($step >= 1)
            class="rounded bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-40">
                1 · Build anchored ledger
            </button>
            <button type="button" wire:click="compromise" @disabled($step < 1 || $step >= 2)
            class="rounded bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-500 disabled:opacity-40">
                2 · Simulate full compromise
            </button>
            <button type="button" wire:click="verifyOffline" @disabled($step < 2)
            class="rounded bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-40"
                    title="chronicle:verify --checkpoints-only">
                3 · Verify offline (--checkpoints-only)
            </button>
            <button type="button" wire:click="verifyAnchors" @disabled($step < 2)
            class="rounded bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-40"
                    title="chronicle:verify --checkpoints-only --anchors (scoped to the anchored checkpoint, as chronicle:anchor:verify)">
                4 · Verify --anchors
            </button>
            <button type="button" wire:click="restore" @disabled($step === 0)
            class="rounded border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-40">
                Reset
            </button>
        </div>

        @if ($anchor)
            <div class="rounded border border-gray-200 bg-gray-50 px-4 py-3 text-sm">
                <p class="font-semibold text-gray-700">
                    Anchored checkpoint
                    <span class="ml-1 rounded bg-green-100 px-1.5 py-0.5 text-[10px] font-medium uppercase text-green-800">RFC 3161 · real TSA</span>
                </p>
                <dl class="mt-2 space-y-0.5 font-mono text-xs text-gray-600">
                    <div><dt class="inline text-gray-400">checkpoint:</dt> {{ $createdCheckpointId }}</div>
                    <div><dt class="inline text-gray-400">tsa:</dt> {{ $anchor['reference'] }}</div>
                    <div><dt class="inline text-gray-400">token:</dt> {{ Str::limit($anchor['proof'] ?? '—', 48) }}</div>
                    <div><dt class="inline text-gray-400">at:</dt> {{ $anchor['anchored_at'] }}</div>
                </dl>
            </div>
        @endif

        @if ($target)
            <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm">
                <p class="font-semibold text-red-800">Forged entry {{ $target['id'] }}</p>
                <dl class="mt-2 space-y-0.5 font-mono text-xs text-red-900">
                    <div><dt class="inline text-red-400">metadata before:</dt> {{ $target['before'] }}</div>
                    <div><dt class="inline text-red-400">metadata after:</dt> {{ $target['after'] }}</div>
                </dl>
                <p class="mt-1 text-xs text-red-700">The whole chain was recomputed and every checkpoint re-signed with a valid key.</p>
            </div>
        @endif

        @if ($offlineValid !== null)
            <div class="rounded-md border px-4 py-3 text-sm
                {{ $offlineValid ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800' }}">
                @if ($offlineValid)
                    ✓ Offline verify passed — {{ $offlineChecked }} checkpoints. The forgery is internally consistent,
                    so a purely local check is fooled.
                @else
                    ✗ Offline verify failed.
                @endif
            </div>
        @endif

        @if ($anchorsValid !== null)
            <div class="rounded-md border px-4 py-3 text-sm
                {{ $anchorsValid ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800' }}">
                @if (! $anchorsValid)
                    ✗ Anchor verify FAILED at checkpoint {{ $anchorsFailedCheckpointId }}
                    (<span class="font-mono text-xs">{{ $anchorsFailureType }}</span>).
                    The TSA token binds the original digest — rewriting the chain changed it, and the attacker
                    can't forge the TSA's prior signature. <strong>The forgery is caught.</strong>
                @else
                    ✓ Anchors valid.
                @endif
            </div>
        @endif
    @endif
</div>
