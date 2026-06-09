<?php

namespace App\Models;

use Database\Factories\ClinicianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'role', 'persona_key'])]
class Clinician extends Model
{
    /** @use HasFactory<ClinicianFactory> */
    use HasFactory;

    //    /**
    //     * @return HasMany<Encounter, $this>
    //     */
    //    public function encounters(): HasMany
    //    {
    //        return $this->hasMany(Encounter::class);
    //    }

    //    /**
    //     * @return HasMany<Prescription, $this>
    //     */
    //    public function prescriptions(): HasMany
    //    {
    //        return $this->hasMany(Prescription::class);
    //    }
}
