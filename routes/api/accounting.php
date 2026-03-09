<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountMappingController;
use App\Http\Controllers\CoaVersionController;
use App\Http\Controllers\FiscalYearController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\PostingJournalController;
use Illuminate\Support\Facades\Route;

// --- Accounting ---
Route::middleware('permission:fiscal_year,true')->group(function () {
    Route::apiResource('fiscal-years', FiscalYearController::class);
    Route::post('fiscal-years/export', [FiscalYearController::class, 'export']);
    Route::post('fiscal-years/import', [FiscalYearController::class, 'import']);
});
Route::middleware('permission:coa_version,true')->group(function () {
    Route::apiResource('coa-versions', CoaVersionController::class);
    Route::post('coa-versions/export', [CoaVersionController::class, 'export']);
    Route::post('coa-versions/import', [CoaVersionController::class, 'import']);
});
Route::middleware('permission:account,true')->group(function () {
    Route::apiResource('accounts', AccountController::class);
    Route::post('accounts/export', [AccountController::class, 'export']);
    Route::post('accounts/import', [AccountController::class, 'import']);
});
Route::middleware('permission:account_mapping,true')->group(function () {
    Route::apiResource('account-mappings', AccountMappingController::class);
    Route::post('account-mappings/export', [AccountMappingController::class, 'export']);
    Route::post('account-mappings/import', [AccountMappingController::class, 'import']);
});
Route::middleware('permission:journal_entry,true')->group(function () {
    Route::apiResource('journal-entries', JournalEntryController::class);
    Route::post('journal-entries/export', [JournalEntryController::class, 'export']);
    Route::post('journal-entries/import', [JournalEntryController::class, 'import']);
});
Route::middleware('permission:posting_journal,true')->group(function () {
    Route::get('posting-journals', [PostingJournalController::class, 'index']);
    Route::post('posting-journals/post', [PostingJournalController::class, 'post']);
});
