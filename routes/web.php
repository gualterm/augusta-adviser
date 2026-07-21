<?php
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\Portal\ClientAuthController;
use App\Http\Controllers\Portal\ClientPortalController;
use App\Http\Controllers\ClientPrivacyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $businessHours = \App\Models\BusinessHour::orderBy('day_of_week')->get();
    return view('welcome', compact('businessHours'));
});

Route::get('/staff', function () {
    return view('staff');
})->name('staff');

Route::post('/contacto/inquerito', [InquiryController::class, 'store'])->name('inquiry.store');

Route::prefix('portal')->name('portal.')->group(function () {

    // Rotas públicas (sem autenticação)
    Route::get('/registo',        [ClientAuthController::class, 'showRegister'])->name('register');
    Route::post('/registo',       [ClientAuthController::class, 'register']);
    Route::get('/login',          [ClientAuthController::class, 'showLogin'])->name('login');
    Route::post('/login',         [ClientAuthController::class, 'login']);
    Route::post('/logout',        [ClientAuthController::class, 'logout'])->name('logout');
    Route::get('/forgot-password',[ClientAuthController::class, 'showForgot'])->name('forgot-password');
    Route::post('/forgot-password',[ClientAuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [ClientAuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password',[ClientAuthController::class, 'processReset'])->name('password.update');

    // Rotas autenticadas (sem requisito de consentimento)
    Route::middleware('auth:client')->group(function () {

        // Verificação de email
        Route::get('/email/verificar', [\App\Http\Controllers\Portal\ClientVerificationController::class, 'notice'])
            ->name('verification.notice');
        Route::get('/email/verificar/{id}/{hash}', [\App\Http\Controllers\Portal\ClientVerificationController::class, 'verify'])
            ->name('verification.verify')->middleware('signed');
        Route::post('/email/reenviar', [\App\Http\Controllers\Portal\ClientVerificationController::class, 'resend'])
            ->name('verification.resend')->middleware('throttle:6,1');

        // Consentimento RGPD (passo 2 do registo)
        Route::get('/consentimento',  [ClientAuthController::class, 'showConsent'])->name('consent');
        Route::post('/consentimento', [ClientAuthController::class, 'saveConsent'])->name('consent.save');

        // Privacidade
        Route::get('/privacidade',    [ClientPrivacyController::class, 'show'])->name('privacy.show');
        Route::patch('/privacidade',  [ClientPrivacyController::class, 'update'])->name('privacy.update');
        Route::post('/privacidade/eliminar', [ClientPrivacyController::class, 'deleteRequest'])->name('privacy.delete-request');

        // Rotas que requerem consentimento RGPD assinado
        Route::middleware('client.consent')->group(function () {
            Route::get('/',                        [ClientPortalController::class, 'dashboard'])->name('dashboard');
            Route::get('/marcar',                  [ClientPortalController::class, 'showBook'])->name('book');
            Route::post('/marcar',                 [ClientPortalController::class, 'book'])->name('book.store');
            Route::get('/remarcar/{id}',           [ClientPortalController::class, 'showReschedule'])->name('reschedule');
            Route::post('/remarcar/{id}',          [ClientPortalController::class, 'saveReschedule'])->name('reschedule.save');
            Route::get('/suggest-slot',            [ClientPortalController::class, 'suggestSlot'])->name('suggest-slot');
            Route::get('/available-slots',         [ClientPortalController::class, 'availableSlots'])->name('available-slots');
            Route::post('/cancelar/{id}',          [ClientAuthController::class, 'cancelAppointment'])->name('cancel');
        });
    });
});

Route::get('/admin/ambiente', function () {
    if (!auth()->check()) return redirect('/admin/login');
    if (auth()->user()->role !== 'admin') return redirect('/admin');
    return view('admin.ambiente');
});