<?php

use App\Http\Controllers\BankReconciliationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:bank_reconciliation,true'])->group(function () {
    Route::post('bank-reconciliations/import-preview', [BankReconciliationController::class, 'importPreview']);
    Route::get('bank-reconciliations', [BankReconciliationController::class, 'index']);
    Route::get('bank-reconciliations/{bank_reconciliation}', [BankReconciliationController::class, 'show']);
    Route::post('bank-reconciliations', [BankReconciliationController::class, 'store']);
    Route::put('bank-reconciliations/{bank_reconciliation}', [BankReconciliationController::class, 'update']);
    Route::delete('bank-reconciliations/{bank_reconciliation}', [BankReconciliationController::class, 'destroy']);
    Route::post('bank-reconciliations/export', [BankReconciliationController::class, 'export']);
    Route::post('bank-reconciliations/{bank_reconciliation}/complete', [BankReconciliationController::class, 'complete']);
    Route::post('bank-reconciliations/{bank_reconciliation}/import-statement', [BankReconciliationController::class, 'importStatement']);
    Route::post('bank-reconciliations/{bank_reconciliation}/auto-match', [BankReconciliationController::class, 'autoMatch']);
    Route::post('bank-reconciliations/{bank_reconciliation}/items/{item}/match', [BankReconciliationController::class, 'matchItem']);
    Route::post('bank-reconciliations/{bank_reconciliation}/items/{item}/unmatch', [BankReconciliationController::class, 'unmatchItem']);
    Route::get('bank-reconciliations/{bank_reconciliation}/unmatched-journal-lines', [BankReconciliationController::class, 'unmatchedJournalLines']);
    Route::post('bank-reconciliations/{bank_reconciliation}/items', [BankReconciliationController::class, 'addItem']);
    Route::delete('bank-reconciliations/{bank_reconciliation}/items/{item}', [BankReconciliationController::class, 'removeItem']);
    Route::put('bank-reconciliations/{bank_reconciliation}/items/{item}/assign-account', [BankReconciliationController::class, 'assignItemAccount']);
});
