<div class="mt-4 space-y-4">
    @if ($throttleMessage)
        <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800">
            {{ $throttleMessage }}
        </div>
    @endif

    <div>
        <p class="text-sm font-semibold text-gray-700">Key ring</p>
        <ul class="mt-2 space-y-1 text-sm">
            @foreach ($keys as $key)
                <li class="flex items-center gap-2 font-mono text-xs">
                    <span class="rounded px-1.5 py-0.5 {{ $key['active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                        {{ $key['active'] ? 'active' : 'in ring' }}
                    </span>
                    <span class="text-gray-700">{{ $key['key_id'] }}</span>
                    <span class="text-gray-400">({{ $key['algorithm'] }})</span>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <button type="button" wire:click="rotate" @disabled($rotated)
        class="rounded bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-40">
            Rotate to {{ \App\Livewire\Lab\KeyRotation::NEW_KEY_ID }}
        </button>
        <button type="button" wire:click="verify" @disabled(! $rotated)
        class="rounded bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-40">
            Verify whole ledger
        </button>
        <button type="button" wire:click="restore" @disabled(! $rotated)
        class="rounded border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-40">
            Reset
        </button>
    </div>

    @if ($rotated)
        <div class="grid gap-3 text-sm md:grid-cols-2">
            <div class="rounded border border-gray-200 bg-gray-50 px-4 py-3">
                <p class="font-semibold text-gray-700">Epoch 1 - retired key</p>
                <p class="mt-1 font-mono text-xs text-gray-600">key: {{ $epoch1Checkpoint['key_id'] }}</p>
                <p class="font-mono text-xs text-gray-600">checkpoint: {{ $epoch1Checkpoint['id'] }}</p>
            </div>
            <div class="rounded border border-gray-200 bg-gray-50 px-4 py-3">
                <p class="font-semibold text-gray-700">Epoch 2 - new active key</p>
                <p class="mt-1 font-mono text-xs text-gray-600">key: {{ $epoch2Checkpoint['key_id'] }}</p>
                <p class="font-mono text-xs text-gray-600">checkpoint: {{ $epoch2Checkpoint['id'] }}</p>
            </div>
        </div>
    @endif

    @if ($verified)
        <div class="rounded-md border px-4 py-3 text-sm
            {{ $valid ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800' }}">
            @if ($valid)
                ✓ Ledger verified - {{ $checked }} entries. Pre-rotation checkpoints still verify under the
                retired key while new checkpoints use {{ \App\Livewire\Lab\KeyRotation::NEW_KEY_ID }}.
            @else
                ✗ Ledger verification failed after rotation.
            @endif
        </div>
    @endif
</div>
