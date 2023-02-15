<?php

use App\Http\Controllers\Payment\BankReconcillationController;
use App\Http\Controllers\Payment\CashVerificationController;
use App\Http\Controllers\Payment\RazorpayPaymentController;
use Illuminate\Support\Facades\Route;

/**
 * | ----------------------------------------------------------------------------------
 * | payment Module Routes |
 * |-----------------------------------------------------------------------------------
 * | Created On-14-11-2022 
 * | Created For-The Routes defined for the payment gateway
 * | Created By-sam kerketa
 */

/**
 * | Created On-14-11-2022 
 * | Created By- sam kerketta
 * | Payment Master for Testing Payment Gateways
    | FLAG : removal of some function / use for testing
 */
Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {
    Route::controller(RazorpayPaymentController::class)->group(function () {
        Route::post('store-payment', 'storePayment');                                               // 01 Store Payment in payment Masters
        Route::get('get-payment-by-id/{id}', 'getPaymentByID');                                     // 02 Get Payment by Id
        Route::get('get-all-payments', 'getAllPayments');                                           // 03 Get All Payments

        # razorpay PG
        Route::post('get-department-byulb', 'getDepartmentByulb');                                  // 04 returning department data according to ulbd 
        Route::post('get-paymentgateway-byrequests', 'getPaymentgatewayByrequests');                // 05 returning payment gateway data according to the request data condition
        Route::post('get-pg-details', 'getPgDetails');                                              // 06 returning the payment gateway details accordin to the request data condition
        Route::get('get-webhook-details', 'getWebhookDetails');                                     // 07 returning all the webhook details 
        Route::post('verify-payment-status', 'verifyPaymentStatus');                                // 08 verifiying the payment status and saving both success, fails, suspeciousdata  
        Route::post('get-transaction-no-details', 'getTransactionNoDetails');                       // 09 geting details of the transaction according to the orderId, paymentId and payment status
        Route::get('all-module-transaction', 'allModuleTransaction');                               // 10 all details of payments according to user Id 
        Route::post('generate-orderid', 'generateOrderid');

        # Payment Reconciliation
        Route::get('get-reconcillation-details', 'getReconcillationDetails');                       // 11 
        Route::post('search-reconciliation-details', 'searchReconciliationDetails');                // 12
        Route::post('update-reconciliation-details', 'updateReconciliationDetails');                // 13
        Route::post('get-tran-by-orderid', 'getTranByOrderId');                                     // 15 Get Transaction by Order ID and payment ID
    });


    /**
     * | Created On-31-01-2023 
     * | Created by-Mrinal Kumar
     * | Payment Cash Verification
     */
    Route::controller(CashVerificationController::class)->group(function () {
        Route::post('list-cash-verification', 'cashVerificationList');
        Route::post('verified-cash-verification', 'verifiedCashVerificationList');
        Route::post('tc-collections', 'tcCollectionDtl');
        Route::post('verified-tc-collections', 'verifiedTcCollectionDtl');
        Route::post('verify-cash', 'cashVerify');
        Route::post('temp-transaction', 'tempTransaction');
    });

    Route::controller(BankReconcillationController::class)->group(function () {
        Route::post('search-transaction', 'searchTransaction');
        Route::post('cheque-dtl-by-id', 'chequeDtlById');
    });
});
Route::controller(RazorpayPaymentController::class)->group(function () {
    Route::post('razerpay-webhook', 'gettingWebhookDetails');                                       // 14 collecting the all data provided by the webhook and updating the related database
});
