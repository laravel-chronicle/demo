<?php

use App\Http\Controllers\PersonaController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::post('/persona', [PersonaController::class, 'store'])->name('persona.switch');

Route::view('/patients', 'stub', ['title' => 'Patients'])->name('patients.index');
Route::view('/ledger', 'stub', ['title' => 'Ledger Explorer'])->name('ledger.index');
Route::view('/lab', 'stub', ['title' => 'Integrity Lab'])->name('lab.index');
Route::view('/auditors', 'stub', ['title' => 'For Auditors'])->name('auditors.index');
Route::view('/how-it-works', 'stub', ['title' => 'How It Works'])->name('how.it.works');
