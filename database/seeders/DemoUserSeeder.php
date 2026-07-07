<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    /**
     * Stable identity for the read-only demo user the audit panel auto-authenticates.
     */
    public const string DEMO_EMAIL = 'auditor@medledger.test';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => self::DEMO_EMAIL],
            ['name' => 'Demo Auditor', 'password' => 'password'],
        );
    }
}
