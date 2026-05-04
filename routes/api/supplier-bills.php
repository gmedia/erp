<?php

use App\Http\Controllers\SupplierBillController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:supplier_bill,true')->group(function () {
    Route::get('supplier-bills', [SupplierBillController::class, 'index']);
    Route::get('supplier-bills/{supplierBill}', [SupplierBillController::class, 'show']);
    Route::post('supplier-bills', [SupplierBillController::class, 'store'])
        ->middleware('permission:supplier_bill.create,true');
    Route::put('supplier-bills/{supplierBill}', [SupplierBillController::class, 'update'])
        ->middleware('permission:supplier_bill.edit,true');
    Route::delete('supplier-bills/{supplierBill}', [SupplierBillController::class, 'destroy'])
        ->middleware('permission:supplier_bill.delete,true');
    Route::post('supplier-bills/export', [SupplierBillController::class, 'export']);
});
