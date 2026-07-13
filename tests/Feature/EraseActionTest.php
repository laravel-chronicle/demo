<?php

use App\Models\Patient;
use App\Models\User;
use App\Support\LedgerVerifier;
use Chronicle\Encryption\SubjectKeyManager;
use Chronicle\Entry\Entry;
use Chronicle\Facades\Chronicle;
use Chronicle\Filament\Resources\ChronicleEntryResource\Pages\ListEntries;
use Database\Seeders\ClinicianSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\PatientSeeder;
use Database\Seeders\SubjectLifecycleSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

beforeEach(function () {
    enableDemoEncryption();
    pinSigningKey();
    $this->seed([ClinicianSeeder::class, PatientSeeder::class, SubjectLifecycleSeeder::class]);

    // The panel auto-logs a read-only demo user in production; replicate that
    // for the Livewire page test and make the audit panel the current one so
    // the plugin's gate closures resolve against this request's session.
    Filament::setCurrentPanel(Filament::getPanel('audit'));
    Auth::login(User::query()->firstOrCreate(
        ['email' => DemoUserSeeder::DEMO_EMAIL],
        ['name' => 'Demo Auditor', 'password' => 'password'],
    ));
});

/**
 * The latest ledger entry that carries the given patient as its subject.
 */
function latestSubjectEntry(Patient $patient): Entry
{
    /** @var Entry $entry */
    $entry = Chronicle::query()->forSubject($patient)->latest()->first();

    return $entry;
}

it('erases a live patient as admin and the chain still verifies', function () {
    session(['demo_persona' => 'admin']);

    $mars = Patient::query()->where('name', 'Mars Vesper')->sole();
    $entry = latestSubjectEntry($mars);

    Livewire::test(ListEntries::class)
        ->callAction(TestAction::make('eraseSubject')->table($entry), data: [
            'confirm_subject' => $entry->subject_type.':'.$entry->subject_id,
            'reason' => 'GDPR Article 17 erasure request (test).',
        ])
        ->assertNotified('Subject erased');

    expect(app(SubjectKeyManager::class)->stateFor(Patient::class, (string) $mars->getKey())->erased)->toBeTrue()
        ->and(Chronicle::query()->forSubject($mars)->action('subject.erased')->count())->toBe(1)
        ->and(app(LedgerVerifier::class)->run()->valid)->toBeTrue();
});

it('blocks erasing a patient under a legal hold', function () {
    session(['demo_persona' => 'admin']);

    $saturn = Patient::query()->where('name', 'Saturn Vesper')->sole();
    $entry = latestSubjectEntry($saturn);

    Livewire::test(ListEntries::class)
        ->callAction(TestAction::make('eraseSubject')->table($entry), data: [
            'confirm_subject' => $entry->subject_type.':'.$entry->subject_id,
            'reason' => 'Attempted erasure of a held subject (test).',
        ])
        ->assertNotified('Subject is on legal hold');

    expect(app(SubjectKeyManager::class)->stateFor(Patient::class, (string) $saturn->getKey())->erased)->toBeFalse();
});

it('hides the erase action from non-admin personas', function () {
    session(['demo_persona' => 'nurse']);

    $mars = Patient::query()->where('name', 'Mars Vesper')->sole();
    $entry = latestSubjectEntry($mars);

    Livewire::test(ListEntries::class)
        ->assertActionHidden(TestAction::make('eraseSubject')->table($entry));
});
