<?php

use App\Http\Controllers\StockAdjustmentController;
use Illuminate\Support\Facades\Route;

uses()->group('supplier-bills', 'ap-payments', 'customer-invoices', 'ar-receipts', 'credit-notes', 'goods-receipts', 'supplier-returns', 'stock-adjustments');

/**
 * Inventaris import untuk modul posting (Refs #367, langkah 4).
 *
 * Kontrak: setiap route POST import untuk modul ini harus menerima status
 * terposting (confirmed/sent/applied/approved) dan memposting jurnal per baris,
 * atau menolak baris dengan 422. Saat ini tidak ada route import yang
 * menerima status terposting; test ini mengunci inventaris tersebut agar
 * perubahan perilaku terlihat.
 */
it('mencatat modul posting yang tidak punya route import', function () {
    $modules = [
        'supplier-bills',
        'ap-payments',
        'customer-invoices',
        'ar-receipts',
        'credit-notes',
        'goods-receipts',
        'supplier-returns',
    ];

    $importRoutes = collect(Route::getRoutes())
        ->filter(fn ($route) => in_array('POST', $route->methods(), true))
        ->map(fn ($route) => $route->uri())
        ->filter(fn (string $uri) => str_ends_with($uri, '/import'))
        ->values()
        ->all();

    foreach ($modules as $module) {
        expect($importRoutes)->not->toContain("api/{$module}/import");
    }
});

it('stock-adjustments import terdaftar tetapi controller tidak punya method import', function () {
    $route = collect(Route::getRoutes())
        ->first(fn ($route) => $route->uri() === 'api/stock-adjustments/import' && in_array('POST', $route->methods(), true));

    expect($route)->not->toBeNull();
    expect(method_exists(StockAdjustmentController::class, 'import'))->toBeFalse();
});
