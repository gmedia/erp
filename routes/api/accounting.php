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
    Route::get('fiscal-years', [FiscalYearController::class, 'index']);

    Route::get('fiscal-years/{fiscal_year}', [FiscalYearController::class, 'show']);

    Route::post('fiscal-years', [FiscalYearController::class, 'store'])->middleware('permission:fiscal_year.create,true');

    Route::put('fiscal-years/{fiscal_year}', [FiscalYearController::class, 'update'])->middleware('permission:fiscal_year.edit,true');

    Route::delete('fiscal-years/{fiscal_year}', [FiscalYearController::class, 'destroy'])->middleware('permission:fiscal_year.delete,true');
    Route::post('fiscal-years/export', [FiscalYearController::class, 'export']);
    Route::post('fiscal-years/import', [FiscalYearController::class, 'import']);
});
Route::middleware('permission:coa_version,true')->group(function () {
    Route::get('coa-versions', [CoaVersionController::class, 'index']);

    Route::get('coa-versions/{coa_version}', [CoaVersionController::class, 'show']);

    Route::post('coa-versions', [CoaVersionController::class, 'store'])->middleware('permission:coa_version.create,true');

    Route::put('coa-versions/{coa_version}', [CoaVersionController::class, 'update'])->middleware('permission:coa_version.edit,true');

    Route::delete('coa-versions/{coa_version}', [CoaVersionController::class, 'destroy'])->middleware('permission:coa_version.delete,true');
    Route::post('coa-versions/export', [CoaVersionController::class, 'export']);
    Route::post('coa-versions/import', [CoaVersionController::class, 'import']);
});
Route::middleware('permission:account,true')->group(function () {
    Route::get('accounts', [AccountController::class, 'index']);

    Route::get('accounts/{account}', [AccountController::class, 'show']);

    Route::post('accounts', [AccountController::class, 'store'])->middleware('permission:account.create,true');

    Route::put('accounts/{account}', [AccountController::class, 'update'])->middleware('permission:account.edit,true');

    Route::delete('accounts/{account}', [AccountController::class, 'destroy'])->middleware('permission:account.delete,true');
    Route::post('accounts/export', [AccountController::class, 'export']);
    Route::post('accounts/import', [AccountController::class, 'import']);
});
Route::middleware('permission:account_mapping,true')->group(function () {
    Route::get('account-mappings', [AccountMappingController::class, 'index']);

    Route::get('account-mappings/{account_mapping}', [AccountMappingController::class, 'show']);

    Route::post('account-mappings', [AccountMappingController::class, 'store'])->middleware('permission:account_mapping.create,true');

    Route::put('account-mappings/{account_mapping}', [AccountMappingController::class, 'update'])->middleware('permission:account_mapping.edit,true');

    Route::delete('account-mappings/{account_mapping}', [AccountMappingController::class, 'destroy'])->middleware('permission:account_mapping.delete,true');
    Route::post('account-mappings/export', [AccountMappingController::class, 'export']);
    Route::post('account-mappings/import', [AccountMappingController::class, 'import']);
});
Route::middleware('permission:journal_entry,true')->group(function () {
    Route::get('journal-entries', [JournalEntryController::class, 'index']);

    Route::get('journal-entries/{journal_entry}', [JournalEntryController::class, 'show']);

    Route::post('journal-entries', [JournalEntryController::class, 'store'])->middleware('permission:journal_entry.create,true');

    Route::put('journal-entries/{journal_entry}', [JournalEntryController::class, 'update'])->middleware('permission:journal_entry.edit,true');

    Route::delete('journal-entries/{journal_entry}', [JournalEntryController::class, 'destroy'])->middleware('permission:journal_entry.delete,true');
    Route::post('journal-entries/export', [JournalEntryController::class, 'export']);
    Route::post('journal-entries/import', [JournalEntryController::class, 'import']);
});
Route::middleware('permission:posting_journal,true')->group(function () {
    Route::get('posting-journals', [PostingJournalController::class, 'index']);
    Route::post('posting-journals/post', [PostingJournalController::class, 'post']);
});
