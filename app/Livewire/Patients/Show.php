<?php

namespace App\Livewire\Patients;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Show extends Component
{
    public function render(): View
    {
        return view('livewire.patients.show');
    }
}
