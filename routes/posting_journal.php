<?php

use App\Http\Controllers\PostingJournalController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('posting-journals', function () {
        return Inertia::render('posting-journals/index');
    })->name('posting-journals')->middleware('permission:posting_journal');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:posting_journal,true')->group(function () {
        Route::prefix('posting-journals')->name('posting-journals.')->group(function () {
            Route::get('/', [PostingJournalController::class, 'index'])->name('index');
            Route::post('/post', [PostingJournalController::class, 'post'])->name('post');
        });
    });
});
