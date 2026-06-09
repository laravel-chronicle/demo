<?php

namespace App\Models;

use App\Support\CurrentClinician;
use Chronicle\Eloquent\HasChronicle;
use Database\Factories\EncounterFactory;
use Illuminate\Container\EntryNotFoundException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

#[Fillable(['patient_id', 'clinician_id', 'reason', 'vitals', 'notes', 'occurred_at'])]
class Encounter extends Model
{
    /** @use HasFactory<EncounterFactory> */
    use HasChronicle, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vitals' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<Clinician, $this>
     */
    public function clinician(): BelongsTo
    {
        return $this->belongsTo(Clinician::class);
    }

    /**
     * @throws CircularDependencyException
     * @throws EntryNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    protected function chronicleActor(): mixed
    {
        return app(CurrentClinician::class)->get();
    }
}
