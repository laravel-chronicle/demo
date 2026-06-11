<?php

use App\Http\Controllers\PersonaController;
use App\Livewire\Lab\Index as LabIndex;
use App\Livewire\Ledger\Index as LedgerIndex;
use App\Livewire\Patients\Index as PatientsIndex;
use App\Livewire\Patients\Show as PatientShow;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::post('/persona', [PersonaController::class, 'store'])->name('persona.switch');
Route::get('/patients', PatientsIndex::class)->name('patients.index');
Route::get('/patients/{patient}', PatientShow::class)->name('patients.show');
Route::get('/ledger', LedgerIndex::class)->name('ledger.index');
Route::get('/lab', LabIndex::class)->name('lab.index');
Route::view('/auditors', 'stub', ['title' => 'For Auditors'])->name('auditors.index');
Route::view('/how-it-works', 'stub', ['title' => 'How It Works'])->name('how.it.works');
