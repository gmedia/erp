<?php

use App\Http\Controllers\CustomerInvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:customer_invoice'])->group(function () {
    Route::get('customer-invoices', [CustomerInvoiceController::class, 'index']);
    Route::post('customer-invoices', [CustomerInvoiceController::class, 'store']);
    Route::get('customer-invoices/{customer_invoice}', [CustomerInvoiceController::class, 'show']);
    Route::put('customer-invoices/{customer_invoice}', [CustomerInvoiceController::class, 'update']);
    Route::delete('customer-invoices/{customer_invoice}', [CustomerInvoiceController::class, 'destroy']);
    Route::post('customer-invoices/export', [CustomerInvoiceController::class, 'export']);
});
