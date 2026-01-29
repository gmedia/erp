<?php

use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// Locale switching route
Route::post('locale/{locale}', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/employee.php';
require __DIR__ . '/position.php';
require __DIR__ . '/department.php';
require __DIR__ . '/permission.php';
require __DIR__ . '/user.php';
require __DIR__ . '/branch.php';
require __DIR__ . '/customer.php';
require __DIR__ . '/supplier.php';
require __DIR__ . '/supplier_category.php';
require __DIR__ . '/customer_category.php';
require __DIR__ . '/product_category.php';
require __DIR__ . '/unit.php';
require __DIR__ . '/product.php';


