<?php

namespace App\Livewire\Ledger;

use App\Models\Clinician;
use App\Support\LedgerVerifier;
use Chronicle\Entry\Entry;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use JsonException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Ledger Explorer - MedLedger')]
class Index extends Component
{
    use WithPagination;

    public bool $verified = false;

    public bool $valid = false;

    public int $checked = 0;

    public ?string $failureReason = null;

    public ?string $failedEntryId = null;

    /**
     * Run a full integrity verification and surface the result in the UI.
     *
     * @throws JsonException
     */
    public function verify(LedgerVerifier $verifier): void
    {
        $outcome = $verifier->run();

        $this->verified = true;
        $this->valid = $outcome->valid;
        $this->checked = $outcome->checked;
        $this->failureReason = $outcome->failureReason;
        $this->failedEntryId = $outcome->entryId;
    }

    public function render(): View
    {
        /** @var LengthAwarePaginator<int, Entry> $entries */
        $entries = Entry::query()->orderByDesc('sequence')->paginate(25);

        return view('livewire.ledger.index', [
            'entries' => $entries,
            'actors' => Clinician::query()->pluck('name', 'id'),
        ]);
    }
}
