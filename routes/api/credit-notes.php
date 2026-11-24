<?php

use App\Http\Controllers\CreditNoteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:credit_note'])->group(function () {
    Route::get('credit-notes', [CreditNoteController::class, 'index']);
    Route::get('credit-notes/{creditNote}', [CreditNoteController::class, 'show']);
    Route::post('credit-notes/export', [CreditNoteController::class, 'export']);

    Route::post('credit-notes', [CreditNoteController::class, 'store'])
        ->middleware('permission:credit_note.create,true');
    Route::put('credit-notes/{creditNote}', [CreditNoteController::class, 'update'])
        ->middleware('permission:credit_note.edit,true');
    Route::delete('credit-notes/{creditNote}', [CreditNoteController::class, 'destroy'])
        ->middleware('permission:credit_note.delete,true');
    Route::post('credit-notes/{creditNote}/apply', [CreditNoteController::class, 'apply'])
        ->middleware('permission:credit_note.edit,true');
});
