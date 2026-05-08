<?php

use App\Http\Controllers\ApPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:ap_payment,true')->group(function () {
    Route::get('ap-payments', [ApPaymentController::class, 'index']);
    Route::get('ap-payments/{apPayment}', [ApPaymentController::class, 'show']);
    Route::post('ap-payments', [ApPaymentController::class, 'store'])
        ->middleware('permission:ap_payment.create,true');
    Route::put('ap-payments/{apPayment}', [ApPaymentController::class, 'update'])
        ->middleware('permission:ap_payment.edit,true');
    Route::delete('ap-payments/{apPayment}', [ApPaymentController::class, 'destroy'])
        ->middleware('permission:ap_payment.delete,true');
    Route::post('ap-payments/export', [ApPaymentController::class, 'export']);
});
