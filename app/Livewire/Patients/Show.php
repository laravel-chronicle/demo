<?php

namespace App\Livewire\Patients;

use App\Models\Clinician;
use App\Models\Patient;
use App\Support\CurrentClinician;
use Chronicle\Contracts\ReferenceResolver;
use Chronicle\Entry\Entry;
use Chronicle\Facades\Chronicle;
use Chronicle\Support\Reference;
use Illuminate\Container\EntryNotFoundException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Patient $patient;

    /**
     * @throws CircularDependencyException
     * @throws Throwable
     * @throws NotFoundExceptionInterface
     * @throws EntryNotFoundException
     * @throws ContainerExceptionInterface
     */
    public function mount(Patient $patient): void
    {
        $this->patient = $patient;

        Chronicle::record()
            ->actor(app(CurrentClinician::class)->get())
            ->action('patient.viewed')
            ->subject($patient)
            ->context(['ip' => request()->ip(), 'reason' => 'Opened patient detail'])
            ->commit();
    }

    public function render(): View
    {
        return view('livewire.patients.show', [
            'trail' => $this->auditTrail(),
            'clinicians' => Clinician::query()->pluck('name', 'id'),
        ]);
    }

    /**
     * The patient's live Chronicle trail: every entry whose subject is the
     * patient or one of the patient's prescriptions/encounters, newest first.
     *
     * @return Collection<int, Entry>
     */
    protected function auditTrail(): Collection
    {
        $this->patient->loadMissing('prescriptions', 'encounters');

        $resolver = app(ReferenceResolver::class);

        $references = collect([$this->patient])
            ->concat($this->patient->prescriptions)
            ->concat($this->patient->encounters)
            ->map(fn (mixed $subject): Reference => $resolver->resolve($subject));

        return Entry::query()
            ->where(function (Builder $query) use ($references): void {
                foreach ($references as $reference) {
                    $query->orWhere(function (Builder $query) use ($reference): void {
                        $query->where('subject_type', $reference->type)
                            ->where('subject_id', $reference->id);
                    });
                }
            })
            ->latestFirst()
            ->get();
    }
}
