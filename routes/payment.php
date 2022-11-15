<?php

use App\Http\Controllers\Payment\RazorpayPaymentController;
use Illuminate\Support\Facades\Route;

/**
 * | ----------------------------------------------------------------------------------
 * | payment Module Routes |
 * |-----------------------------------------------------------------------------------
 * | Created On-14-11-2022 
 * | Created For-The Routes defined for the payment gateway through razorpay
 * | Created By-sam kerketa
 */

/**
     * | Created On-14-11-2022 
     * | Created By- sam kerketta
     * | Payment Master for Testing Payment Gateways
     */
    Route::controller(RazorpayPaymentController::class)->group(function () {
        Route::post('store-payment', 'storePayment');                           // Store Payment in payment Masters
        Route::get('get-payment-by-id/{id}', 'getPaymentByID');                 // Get Payment by Id
        Route::get('get-all-payments', 'getAllPayments');                       // Get All Payments

        // razorpay PG
        Route::post('get-department-byulb', 'getDepartmentByulb'); 
    });