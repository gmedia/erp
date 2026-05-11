<?php

use App\Http\Controllers\RecurringJournalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('recurring-journals', [RecurringJournalController::class, 'index']);
    Route::get('recurring-journals/{recurring_journal}', [RecurringJournalController::class, 'show']);
    Route::post('recurring-journals', [RecurringJournalController::class, 'store']);
    Route::put('recurring-journals/{recurring_journal}', [RecurringJournalController::class, 'update']);
    Route::delete('recurring-journals/{recurring_journal}', [RecurringJournalController::class, 'destroy']);
    Route::post('recurring-journals/export', [RecurringJournalController::class, 'export']);
    Route::post('recurring-journals/{recurring_journal}/execute', [RecurringJournalController::class, 'execute']);
});
