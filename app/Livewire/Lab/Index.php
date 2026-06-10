<?php

namespace App\Livewire\Lab;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Integrity Lab — MedLedger')]
class Index extends Component
{
    public function render(): View
    {
        return view('livewire.lab.index');
    }
}
