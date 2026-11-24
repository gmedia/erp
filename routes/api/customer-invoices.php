<?php

use App\Http\Controllers\CustomerInvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:customer_invoice'])->group(function () {
    Route::get('customer-invoices', [CustomerInvoiceController::class, 'index']);
    Route::get('customer-invoices/{customer_invoice}', [CustomerInvoiceController::class, 'show']);
    Route::post('customer-invoices/export', [CustomerInvoiceController::class, 'export']);

    Route::post('customer-invoices', [CustomerInvoiceController::class, 'store'])
        ->middleware('permission:customer_invoice.create,true');
    Route::put('customer-invoices/{customer_invoice}', [CustomerInvoiceController::class, 'update'])
        ->middleware('permission:customer_invoice.edit,true');
    Route::delete('customer-invoices/{customer_invoice}', [CustomerInvoiceController::class, 'destroy'])
        ->middleware('permission:customer_invoice.delete,true');
});
