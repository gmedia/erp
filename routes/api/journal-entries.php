<?php

use App\Http\Controllers\JournalEntryController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:journal_entry,true')->group(function () {
    Route::get('journal-entries', [JournalEntryController::class, 'index']);

    Route::get('journal-entries/{journal_entry}', [JournalEntryController::class, 'show']);

    Route::post('journal-entries', [JournalEntryController::class, 'store'])->middleware('permission:journal_entry.create,true');

    Route::put('journal-entries/{journal_entry}', [JournalEntryController::class, 'update'])->middleware('permission:journal_entry.edit,true');

    Route::delete('journal-entries/{journal_entry}', [JournalEntryController::class, 'destroy'])->middleware('permission:journal_entry.delete,true');
    Route::post('journal-entries/export', [JournalEntryController::class, 'export']);
    Route::post('journal-entries/import', [JournalEntryController::class, 'import'])->middleware('permission:journal_entry.create,true');
});
