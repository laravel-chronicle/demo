<?php

use App\Http\Controllers\PersonaController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::post('/persona', [PersonaController::class, 'store'])->name('persona.switch');
