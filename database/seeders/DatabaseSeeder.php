<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DemoUserSeeder::class,
            ClinicianSeeder::class,
            PatientSeeder::class,
            SubjectLifecycleSeeder::class,
            LedgerCheckpointSeeder::class,
        ]);
    }
}
