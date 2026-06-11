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

    public string $allergies = '';

    public string $drug = '';

    public string $dose = '';

    public string $amendment = '';

    public string $amendmentReason = '';

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

        $this->allergies = (string) $patient->allergies;

        Chronicle::record()
            ->actor(app(CurrentClinician::class)->get())
            ->action('patient.viewed')
            ->subject($patient)
            ->context(['ip' => request()->ip(), 'reason' => 'Opened patient detail'])
            ->commit();
    }

    public function saveAllergies(): void
    {
        $this->validate(['allergies' => ['required', 'string', 'max:255']]);

        $this->patient->allergies = $this->allergies;
        $this->patient->save(); // fires HasChronicle => 'patient.updated' with an automatic diff

        session()->flash('status', 'Allergies updated.');
    }

    /**
     * @throws CircularDependencyException
     * @throws EntryNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function prescribe(): void
    {
        $this->validate([
            'drug' => ['required', 'string', 'max:255'],
            'dose' => ['required', 'string', 'max:255'],
        ]);

        $this->patient->prescriptions()->create([
            'clinician_id' => app(CurrentClinician::class)->get()->getKey(),
            'drug' => $this->drug,
            'dose' => $this->dose,
            'status' => 'active',
        ]); // fires HasChronicle => 'prescription.created'

        $this->reset('drug', 'dose');
        session()->flash('status', 'Prescription recorded.');
    }

    /**
     * @throws CircularDependencyException
     * @throws NotFoundExceptionInterface
     * @throws EntryNotFoundException
     * @throws Throwable
     * @throws ContainerExceptionInterface
     */
    public function amend(): void
    {
        $this->validate([
            'amendment' => ['required', 'string', 'max:1000'],
            'amendmentReason' => ['required', 'string', 'max:255'],
        ]);

        $old = $this->patient->notes ?? '';

        // A formal amendment: persist the change WITHOUT the automatic
        // 'patient.updated' entry, then record an explicit, reason-bearing
        // 'patient.amended' entry with a before/after diff.
        $this->patient->notes = $this->amendment;
        $this->patient->saveQuietly();

        Chronicle::record()
            ->actor(app(CurrentClinician::class)->get())
            ->action('patient.amended')
            ->subject($this->patient)
            ->diff(['notes' => ['old' => $old, 'new' => $this->amendment]])
            ->context(['reason' => $this->amendmentReason])
            ->commit();

        $this->reset('amendment', 'amendmentReason');
        session()->flash('status', 'Record amended.');
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
