<?php

namespace App\Livewire\Lab;

use App\Livewire\Lab\Concerns\ThrottlesDestructiveActions;
use App\Models\Clinician;
use App\Support\LedgerVerifier;
use Chronicle\Entry\Entry;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use JsonException;
use Livewire\Component;

class TamperSimulator extends Component
{
    use ThrottlesDestructiveActions;

    public ?string $selectedId = null;

    public bool $baselineValid = true;

    public int $baselineChecked = 0;

    public bool $tampered = false;

    public ?string $attack = null;

    public bool $valid = true;

    public int $checked = 0;

    public ?string $failureType = null;

    public ?string $failureReason = null;

    public ?string $failedEntryId = null;

    /**
     * The raw column snapshot of the tampered row, used to restore on reset.
     *
     * @var array<string, mixed>|null
     */
    public ?array $snapshot = null;

    public bool $snapshotWasDeleted = false;

    /**
     * Establish the "before (valid)" baseline once when the panel mounts.
     *
     * @throws JsonException
     */
    public function mount(LedgerVerifier $verifier): void
    {
        $this->refreshBaseline($verifier);
    }

    public function selectEntry(string $id): void
    {
        $this->selectedId = $id;
    }

    /**
     * Scrub: raw DELETE of the selected entry, bypassing Chronicle. Picks a
     * non-tail entry so the chain genuinely breaks for its successor.
     *
     * @throws JsonException
     */
    public function scrub(LedgerVerifier $verifier): void
    {
        if (! $this->passesThrottle('tamper')) {
            return;
        }

        $row = $this->selectedRow();

        if ($row === null || ! $this->hasSuccessor($row)) {
            return;
        }

        $this->snapshot = $row->getAttributes();
        $this->snapshotWasDeleted = true;

        DB::table('chronicle_entries')->where('id', $row->id)->delete();

        $this->attack = 'scrub';
        $this->tampered = true;
        $this->runVerify($verifier);
    }

    /**
     * Alter: raw UPDATE of a hash-covered column (action), bypassing Chronicle.
     *
     * @throws JsonException
     */
    public function alter(LedgerVerifier $verifier): void
    {
        if (! $this->passesThrottle('tamper')) {
            return;
        }

        $row = $this->selectedRow();

        if ($row === null) {
            return;
        }

        $this->snapshot = $row->getAttributes();
        $this->snapshotWasDeleted = false;

        DB::table('chronicle_entries')
            ->where('id', $row->id)
            ->update(['action' => $row->action.'.tampered']);

        $this->attack = 'alter';
        $this->tampered = true;
        $this->runVerify($verifier);
    }

    /**
     * Restore the tampered row exactly and clear the panel state.
     *
     * Named `restore()` (not `reset()`) deliberately: Livewire's base Component
     * already defines `reset()` for clearing properties, which we call below.
     *
     * @throws JsonException
     */
    public function restore(LedgerVerifier $verifier): void
    {
        if ($this->snapshot !== null) {
            if ($this->snapshotWasDeleted) {
                DB::table('chronicle_entries')->insert($this->snapshot);
            } else {
                $id = $this->snapshot['id'];
                $values = $this->snapshot;
                unset($values['id']);
                DB::table('chronicle_entries')->where('id', $id)->update($values);
            }
        }

        $this->reset('selectedId', 'tampered', 'attack', 'valid', 'checked',
            'failureType', 'failureReason', 'failedEntryId', 'snapshot', 'snapshotWasDeleted');

        $this->refreshBaseline($verifier);
    }

    private function selectedRow(): ?Entry
    {
        if ($this->selectedId === null) {
            return null;
        }

        return Entry::query()->find($this->selectedId);
    }

    private function hasSuccessor(Entry $row): bool
    {
        return DB::table('chronicle_entries')->where('sequence', '>', $row->sequence)->exists();
    }

    /**
     * @throws JsonException
     */
    private function runVerify(LedgerVerifier $verifier): void
    {
        $outcome = $verifier->run();

        $this->valid = $outcome->valid;
        $this->checked = $outcome->checked;
        $this->failureType = $outcome->failureType;
        $this->failureReason = $outcome->failureReason;
        $this->failedEntryId = $outcome->entryId;
    }

    /**
     * @throws JsonException
     */
    private function refreshBaseline(LedgerVerifier $verifier): void
    {
        $outcome = $verifier->run();
        $this->baselineValid = $outcome->valid;
        $this->baselineChecked = $outcome->checked;
    }

    public function render(): View
    {
        $entries = Entry::query()
            ->orderBy('sequence')
            ->limit(40)
            ->get(['id', 'sequence', 'action', 'actor_type', 'actor_id']);

        return view('livewire.lab.tamper-simulator', [
            'entries' => $entries,
            'actors' => Clinician::query()->pluck('name', 'id'),
        ]);
    }
}
