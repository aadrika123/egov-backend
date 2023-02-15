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
     * | Created by-Anshu Kumar
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
        # Citizen View Water Screen For Mobile 
        Route::post('list-connection-type', 'getConnectionType');                                        //05        // Get Water Connection Type Details mstr
        Route::post('list-connection-through', 'getConnectionThrough');                                  //06        // Get Water Connection Through Details mstr
        Route::post('list-property-type', 'getPropertyType');                                            //07        // Get Property Type Details mstr
        Route::post('list-owner-type', 'getOwnerType');                                                  //08        // Get Owner Type Details mstr
        Route::post('list-ward-no', 'getWardNo');                                                        //09        // Get Ward No According to Saf or Holding Details mstr

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
        Route::post('doc-verify-reject', 'docVerifyReject');                                            // Workflow
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

        Route::post('final-submit-application', 'finalSubmitionApplication');                           // Not Used
    });

    /**
     * | Created on : 10-02-2023
     * | Created By : Sam kerketta
     * |-------------- Water transaction and Payment related ---------------|
     */
    Route::controller(WaterPaymentController::class)->group(function () {
        # Consumer And Citizen Transaction Operation
        Route::post('consumer/get-payment-history', 'getConsumerPaymentHistory');                       // Consumer
        Route::post('generate-payment-receipt', 'generatePaymentReceipt');                              // Citizen
        Route::post('consumer/generate-demand-receiep', 'generateDemandPaymentReceipt');                // Consumer
    });

    /**
     * | Created On : 11-02-2023
     * | Created By : Sam kerketta
     * |------------- Water Consumer and Related -------------|
     */
    Route::controller(WaterConsumer::class)->group(function () {
        Route::post('consumer/list-demand', 'listConsumerDemand');                                      // Consumer
    });
});

/**
 * | Created On:09-12-2022 
 * | Created by:Sandeep Bara
 * | ------------------- Water Connection / mobile ------------------------ |
 */
Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {
    Route::controller(WaterApplication::class)->group(function () {
        Route::get('citizenApplications', 'getCitizenApplication');                                     //10
        Route::post('Razorpay-Orderid', 'handeRazorPay');                                               //11
        Route::post('getTranNo', 'readTransectionAndApl');                                              //12
    });
});
Route::controller(WaterApplication::class)->group(function () {
    Route::post('payment-recipt', 'paymentRecipt');                                                     //15
    Route::post('cargeCal', 'calWaterConCharge');                                                       //16
    Route::post('consumerChargeCal', 'calConsumerDemand');                                              //17
});
Route::controller(WaterConsumer::class)->group(function () {
    Route::post('consumerChargeCal', 'calConsumerDemand');                                              //18        
});
