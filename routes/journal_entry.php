<?php

use App\Http\Controllers\JournalEntryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('journal-entries', function () {
        return Inertia::render('journal-entries/index');
    })->name('journal-entries')->middleware('permission:journal_entry');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:journal_entry,true')->group(function () {
        Route::prefix('journal-entries')->name('journal-entries.')->group(function () {
            Route::get('/', [JournalEntryController::class, 'index'])->name('index');
            Route::post('/export', [JournalEntryController::class, 'export'])->name('export');
            Route::post('/', [JournalEntryController::class, 'store'])->name('store')->middleware('permission:journal_entry.create,true');
            Route::get('/{journalEntry}', [JournalEntryController::class, 'show'])->name('show');
            Route::put('/{journalEntry}', [JournalEntryController::class, 'update'])->name('update')->middleware('permission:journal_entry.edit,true');
            Route::delete('/{journalEntry}', [JournalEntryController::class, 'destroy'])->name('destroy')->middleware('permission:journal_entry.delete,true');
        });
    });
});
