<?php

use App\Http\Controllers\PostingJournalController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:posting_journal,true')->group(function () {
    Route::get('posting-journals', [PostingJournalController::class, 'index']);
    Route::post('posting-journals/post', [PostingJournalController::class, 'post']);
});
