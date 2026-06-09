<?php

namespace App\Livewire\Patients;

use App\Models\Patient;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Patients — MedLedger')]
class Index extends Component
{
    public function render(): View
    {
        /** @var Collection<int, Patient> $patients */
        $patients = Patient::query()->orderBy('name')->get();

        return view('livewire.patients.index', ['patients' => $patients]);
    }
}
