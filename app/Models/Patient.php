<?php

namespace App\Models;

use App\Support\CurrentClinician;
use Chronicle\Eloquent\HasChronicle;
use Database\Factories\PatientFactory;
use Illuminate\Container\EntryNotFoundException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

#[Fillable(['name', 'dob', 'mrn', 'allergies', 'notes'])]
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasChronicle, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dob' => 'date',
        ];
    }

    /**
     * @return HasMany<Encounter, $this>
     */
    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class);
    }

    /**
     * @return HasMany<Prescription, $this>
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * @throws CircularDependencyException
     * @throws NotFoundExceptionInterface
     * @throws EntryNotFoundException
     * @throws ContainerExceptionInterface
     */
    protected function chronicleActor(): mixed
    {
        return app(CurrentClinician::class)->get();
    }
}
