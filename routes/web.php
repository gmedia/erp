<?php

use Illuminate\Support\Facades\Route;

// Catch-all route for React Route SPA
// Excludes '/api' prefix so API routes are handled by routes/api.php
Route::get('{any}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
