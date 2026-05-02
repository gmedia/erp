<?php

use App\Http\Controllers\CreditNoteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:credit_note'])->group(function () {
    Route::get('/credit-notes', [CreditNoteController::class, 'index']);
    Route::post('/credit-notes', [CreditNoteController::class, 'store']);
    Route::get('/credit-notes/{credit_note}', [CreditNoteController::class, 'show']);
    Route::put('/credit-notes/{credit_note}', [CreditNoteController::class, 'update']);
    Route::delete('/credit-notes/{credit_note}', [CreditNoteController::class, 'destroy']);
    Route::post('/credit-notes/export', [CreditNoteController::class, 'export']);
});
