<?php

use App\Http\Controllers\Api\MpesaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| These routes are loaded by bootstrap/app.php with the "api" middleware
| group. The M-Pesa callback is CSRF-exempt by design (external callback).
*/

Route::prefix('mpesa')->name('mpesa.')->group(function () {
    // Safaricom posts the STK push result here (must be public, no auth)
    Route::post('/callback', [MpesaController::class, 'callback'])->name('callback');

    // Authenticated routes (member portal polling)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/status/{checkoutRequestId}', [MpesaController::class, 'queryStatus'])->name('status');

        // ── Test / local-mode only ──────────────────────────────────────────
        // POST /api/mpesa/test/simulate  { checkout_request_id, phone, amount }
        // Creates a fake MpesaTransaction so portal polling works without real Safaricom
        Route::post('/test/simulate', [MpesaController::class, 'simulatePayment'])->name('test.simulate');
    });
});
