<?php

namespace Database\Seeders;

use App\Models\Clinician;
use Illuminate\Database\Seeder;

class ClinicianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var array<string, array{name: string, role: string}> $personas */
        $personas = (array) config('demo.personas');

        foreach ($personas as $key => $persona) {
            Clinician::query()->updateOrCreate(
                ['persona_key' => $key],
                ['name' => $persona['name'], 'role' => $persona['role']],
            );
        }
    }
}
