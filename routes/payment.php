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
Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {
    Route::controller(RazorpayPaymentController::class)->group(function () {
        Route::post('store-payment', 'storePayment');                                               // Store Payment in payment Masters
        Route::get('get-payment-by-id/{id}', 'getPaymentByID');                                     // Get Payment by Id
        Route::get('get-all-payments', 'getAllPayments');                                           // Get All Payments

        # razorpay PG
        Route::post('get-department-byulb', 'getDepartmentByulb');                                  // returning department data according to ulbd 
        Route::post('get-paymentgateway-byrequests', 'getPaymentgatewayByrequests');                // returning payment gateway data according to the request data condition
        Route::post('get-pg-details', 'getPgDetails');                                              // returning the payment gateway details accordin to the request data condition
        Route::get('get-webhook-details', 'getWebhookDetails');                                     // returning all the webhook details 
        Route::post('get-order-id', 'getTraitOrderId'); //<----------------- here (INVALID)
        Route::post('verify-payment-status', 'verifyPaymentStatus');                                // verifiying the payment status and saving both success, fails, suspeciousdata  
        Route::post('get-transaction-no-details', 'getTransactionNoDetails');    
        
        # Payment Reconciliation
        Route::get('get-reconcillation-details', 'getReconcillationDetails'); 
        Route::post('search-reconciliation-details', 'searchReconciliationDetails');
        Route::post('update-reconciliation-details', 'updateReconciliationDetails');
    });
});
Route::controller(RazorpayPaymentController::class)->group(function () {
Route::post('razerpay-webhook', 'gettingWebhookDetails');                                           // collecting the all data provided by the webhook and updating the related database
Route::post('all-module-transaction','allModuleTransaction');
});