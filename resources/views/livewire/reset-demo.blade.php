<span class="inline-flex items-center gap-2 text-xs">
    @if ($message)
        <span class="rounded bg-amber-100 px-2 py-0.5 text-amber-900">{{ $message }}</span>
    @endif
    <button type="button" wire:click="resetDemo" wire:loading.attr="disabled" wire:target="resetDemo"
            class="rounded border border-amber-700/50 px-2 py-0.5 font-semibold text-amber-950 hover:bg-amber-100 disabled:opacity-50">
        <span wire:loading.remove wire:target="resetDemo">Reset demo</span>
        <span wire:loading wire:target="resetDemo">Resetting…</span>
    </button>
</span>
