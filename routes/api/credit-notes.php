<?php

use App\Http\Controllers\CreditNoteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:credit_note'])->group(function () {
    Route::get('credit-notes', [CreditNoteController::class, 'index']);
    Route::post('credit-notes', [CreditNoteController::class, 'store']);
    Route::get('credit-notes/{creditNote}', [CreditNoteController::class, 'show']);
    Route::put('credit-notes/{creditNote}', [CreditNoteController::class, 'update']);
    Route::delete('credit-notes/{creditNote}', [CreditNoteController::class, 'destroy']);
    Route::post('credit-notes/{creditNote}/apply', [CreditNoteController::class, 'apply']);
    Route::post('credit-notes/export', [CreditNoteController::class, 'export']);
});
