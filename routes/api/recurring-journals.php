<?php

use App\Http\Controllers\RecurringJournalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:recurring_journal,true'])->group(function () {
    Route::get('recurring-journals', [RecurringJournalController::class, 'index']);
    Route::get('recurring-journals/{recurring_journal}', [RecurringJournalController::class, 'show']);
    Route::post('recurring-journals/export', [RecurringJournalController::class, 'export']);

    Route::post('recurring-journals', [RecurringJournalController::class, 'store'])
        ->middleware('permission:recurring_journal.create,true');
    Route::put('recurring-journals/{recurring_journal}', [RecurringJournalController::class, 'update'])
        ->middleware('permission:recurring_journal.edit,true');
    Route::delete('recurring-journals/{recurring_journal}', [RecurringJournalController::class, 'destroy'])
        ->middleware('permission:recurring_journal.delete,true');
    Route::post('recurring-journals/{recurring_journal}/execute', [RecurringJournalController::class, 'execute'])
        ->middleware('permission:recurring_journal.edit,true');
});
