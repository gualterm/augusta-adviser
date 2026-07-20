<?php

use Illuminate\Support\Facades\Route;

// Formulário de consentimento RGPD (PWA)
Route::post('/consent', [\App\Http\Controllers\ConsentController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('consent.store');
