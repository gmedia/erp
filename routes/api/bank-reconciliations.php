<?php

use App\Http\Controllers\BankReconciliationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('bank-reconciliations', [BankReconciliationController::class, 'index']);
    Route::get('bank-reconciliations/{bank_reconciliation}', [BankReconciliationController::class, 'show']);
    Route::post('bank-reconciliations', [BankReconciliationController::class, 'store']);
    Route::put('bank-reconciliations/{bank_reconciliation}', [BankReconciliationController::class, 'update']);
    Route::delete('bank-reconciliations/{bank_reconciliation}', [BankReconciliationController::class, 'destroy']);
    Route::post('bank-reconciliations/export', [BankReconciliationController::class, 'export']);
    Route::post('bank-reconciliations/{bank_reconciliation}/complete', [BankReconciliationController::class, 'complete']);
    Route::post('bank-reconciliations/{bank_reconciliation}/items', [BankReconciliationController::class, 'addItem']);
    Route::delete('bank-reconciliations/{bank_reconciliation}/items/{item}', [BankReconciliationController::class, 'removeItem']);
});
