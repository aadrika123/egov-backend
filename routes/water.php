<?php

use App\Http\Controllers\Water\NewConnectionController;
use App\Http\Controllers\water\WaterApplication;
use App\Http\Controllers\Water\WaterConsumer;
use App\Http\Controllers\Water\WaterPaymentController;
use Illuminate\Support\Facades\Route;

/**
 * | ----------------------------------------------------------------------------------
 * | Water Module Routes |
 * |-----------------------------------------------------------------------------------
 * | Created On-06-10-2022 
 * | Created For-The Routes defined for the Water Usage Charge Management System Module
 * | Created By-Anshu Kumar
 */

Route::post('/apply-new-connection', function () {
    dd('Welcome to simple Water route file');
});

Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {
    /**
     * | Created On-07-10-2022 
     * | Updated by-Sam kerketta
     * | ------------------- Apply New Water Connection ------------------------ |
     */
    Route::resource('application/apply-new-connection', NewConnectionController::class);                             //01
    /**
     * | Created On:08-11-2022 
     * | Created by:Sam Kerketta
     * | ------------------- Water Connection / mobile ------------------------ |
     */
    Route::controller(NewConnectionController::class)->group(function () {
        # water Workflow
        Route::post('inbox', 'waterInbox');                                                             // Workflow
        Route::post('outbox', 'waterOutbox');                                                           // Workflow
        Route::post('post-next-level', 'postNextLevel');                                                // Workflow
        Route::post('workflow/application/get-by-id', 'getApplicationsDetails');                        // Workflow
        Route::post('special-inbox', 'waterSpecialInbox');                                              // Workflow
        Route::post('escalate', 'postEscalate');                                                        // Workflow                     
        Route::post('application/approval-rejection', 'approvalRejectionWater');                        // Workflow
        Route::post('comment-independent', 'commentIndependent');                                       // Workflow
        Route::post('field-verified-inbox', 'fieldVerifiedInbox');                                      // Workflow
        Route::post('site-verification', 'fieldVerification');                                          // Workflow
        Route::post('back-to-citizen', 'backToCitizen');                                                // Workflow
        Route::post('btc-inbox', 'btcInbox');                                                           // Workflow
        Route::post('workflow/get-doc-list', 'getDocList');                                             // Workflow
        Route::post('doc-verify-reject', 'docVerifyRejects');                                           // Workflow
        Route::post('upload-document', 'uploadWaterDoc');                                               // Workflow/Citizen
        Route::post('get-upload-documents', 'getUploadDocuments');                                      // Workflow/Citizen  

        Route::Post('application/delete', 'deleteWaterApplication');                                    // Citizen     
        Route::post('application/get-by-id', 'getApplicationDetails');                                  // Citizen
        Route::post('citizen/get-doc-list', 'getDocToUpload');                                          // Citizen  
        Route::post('application/edit', 'editWaterAppliction');                                         // Citizen/Admin
        Route::post('search-holding-saf', 'getSafHoldingDetails');                                      // Admin
        Route::post('application/search', 'getActiveApplictaions');                                     // Admin
        Route::post('admin/application/get-details-by-id', 'getApplicationDetailById');                 // Admin
        Route::post('admin/application/list-details-by-date', 'listApplicationBydate');                 // Admin
        Route::post('search-consumer', 'searchWaterConsumer');                                          // Admin/Consumer   
        Route::post('consumer/get-listed-details', 'approvedWaterApplications');                        // Consumer

        # Site Inspection
        Route::post('admin/search-application', 'searchApplicationByParameter');                        // Admin
        Route::post('admin/site-verification/comparision', 'listComparision');                          // Admin / notused
        Route::post('admin/application/save-inspection-date', 'saveInspectionDateTime');                // Admin
        Route::post('admin/application/site-inspection-details', 'getSiteInspectionDetails');           // Admin
        Route::post('admin/application/cancel-inspection-scheduling', 'cancelSiteInspection');          // Admin
        Route::post('admin/application/je-site-details', 'getJeSiteDetails');                           // Admin
        Route::post('admin/application/online-site-inspection', '');     // Admin
    });

    /**
     * | Created on : 10-02-2023
     * | Created By : Sam kerketta
     * |-------------- Water transaction and Payment related ---------------|
     */
    Route::controller(WaterPaymentController::class)->group(function () {
        # Consumer And Citizen Transaction Operation
        Route::post('master/get-listed-details', 'getWaterMasterData');                                 // Admin/ Citizen
        Route::post('consumer/get-payment-history', 'getConsumerPaymentHistory');                       // Consumer
        Route::post('generate-payment-receipt', 'generatePaymentReceipt');                              // Citizen
        Route::post('admin/application/generate-payment-receipt', 'generateOfflinePaymentReceipt');     // Consumer

        Route::post('site-verification/save-site-details', 'saveSitedetails');                          // Admin
        Route::post('application/payment/offline/pay-connection-charge', 'offlineConnectionPayment');   // Admin
        Route::post('application/payment/get-payment-history', 'getApplicationPaymentHistory');         // Admin / Consumer
    });

    /**
     * | Created On : 11-02-2023
     * | Created By : Sam kerketta
     * |------------- Water Consumer and Related -------------|
     */
    Route::controller(WaterConsumer::class)->group(function () {
        Route::post('consumer/list-demand', 'listConsumerDemand');                                      // Consumer
        Route::post('admin/consumer/generate-demand', 'saveGenerateConsumerDemand');                    // Admin /
        Route::post('admin/consumer/save-connection-meter', 'saveUpdateMeterDetails');                  // Admin
        Route::post('admin/consumer/get-meter-list', 'getMeterList');                                   // Admin

        # Deactivation
        Route::post('admin/consumer/apply-deactivation', 'applyDeactivation');                          // Admin / Not Used
    });
});

/**
 * | Created On:09-12-2022 
 * | Created by:Sandeep Bara
 * | Modified by: Sam kerketta
 * | Modified on: 11-01-2023
 * | ------------------- Water Connection / mobile ------------------------ |
 */
Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {
    Route::controller(WaterApplication::class)->group(function () {
        Route::get('citizenApplications', 'getCitizenApplication');                                     //10
        Route::post('Razorpay-Orderid', 'handeRazorPay');                                               //11
        Route::post('getTranNo', 'readTransectionAndApl');                                              //12

        # Dashbording Api
        Route::post('jsk/application/get-applied-list', 'getJskAppliedApplication'); // Route
    });
});
Route::controller(WaterApplication::class)->group(function () {
    Route::post('payment-recipt', 'paymentRecipt');                                                     //15
    Route::post('cargeCal', 'calWaterConCharge');                                                       //16
    Route::post('consumerChargeCal', 'calConsumerDemand');                                              //17
    Route::post('payment-water', 'paymentWater');                                                       // Not Build
});
Route::controller(WaterConsumer::class)->group(function () {
    Route::post('consumerChargeCal', 'calConsumerDemand');                                              //18        
});
