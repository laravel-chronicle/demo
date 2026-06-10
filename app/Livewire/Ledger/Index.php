<?php

namespace App\Livewire\Ledger;

use App\Models\Clinician;
use Chronicle\Entry\Entry;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Ledger Explorer — MedLedger')]
class Index extends Component
{
    use WithPagination;

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
