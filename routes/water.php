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
        Route::post('list-connection-type', 'getConnectionType');                                        //05         // Get Water Connection Type Details mstr
        Route::post('list-connection-through', 'getConnectionThrough');                                  //06        // Get Water Connection Through Details mstr
        Route::post('list-property-type', 'getPropertyType');                                            //07        // Get Property Type Details mstr
        Route::post('list-owner-type', 'getOwnerType');                                                  //08        // Get Owner Type Details mstr
        Route::post('list-ward-no', 'getWardNo');                                                        //09        // Get Ward No According to Saf or Holding Details mstr

        # water Workflow
        Route::post('inbox', 'waterInbox');                                                        // 
        Route::post('outbox', 'waterOutbox');                                                      //
        Route::post('post-next-level', 'postNextLevel');                                                //
        // Route::post('get-pending-application-by-id', 'getApplicationsDetails');   
        Route::post('workflow/application/get-by-id', 'getApplicationsDetails');                      //
        Route::post('special-inbox', 'waterSpecialInbox');                                         //
        Route::post('escalate', 'postEscalate');                                                        //       
        // Route::post('approval-rejection-application', 'approvalRejectionWater');                        //
        Route::post('application/approval-rejection', 'approvalRejectionWater');        
        // Route::post('post-message', 'commentIndependent');      
        Route::post('comment-independent', 'commentIndependent');                                           //
        // Route::post('get-approved-water-application-details', 'approvedWaterApplications');             //       
        Route::post('consumer/get-listed-details', 'approvedWaterApplications'); 
        Route::post('field-verified-inbox', 'fieldVerifiedInbox');                                 //
        Route::post('site-verification', 'fieldVerification');                                         // 
        Route::post('back-to-citizen', 'backToCitizen');                                                //
        Route::post('btc-inbox', 'btcInbox');                                                      //
        // Route::Post('delete-application', 'deleteWaterApplication');      
        Route::Post('application/delete', 'deleteWaterApplication');                               //       
        // Route::post('get-application-details', 'getApplicationDetails'); 
        Route::post('application/get-by-id', 'getApplicationDetails');                                //
        Route::post('upload-document', 'uploadWaterDoc');                                               //
        Route::post('get-upload-documents', 'getUploadDocuments');                                      //
        // Route::post('list-doc-to-upload', 'getDocToUpload');                                            //
        Route::post('citizen/get-doc-list', 'getDocToUpload');
        // Route::post('get-related-saf-holding', 'getSafHoldingDetails');
        Route::post('search-holding-saf', 'getSafHoldingDetails');
        // Route::post('final-submit-application', 'finalSubmitionApplication');
        // Route::post('search-water-consumer', 'searchWaterConsumer');
        Route::post('search-consumer', 'searchWaterConsumer');
        Route::post('application/search', 'getActiveApplictaions');

        Route::post('workflow/get-doc-list', 'getDocList');

        // Route::post('list-doc', 'getWaterDocDetails');                                                  //
        // Route::post('verify-doc', 'waterDocStatus');                                                    // 
        // Route::post('generate-payment-receipt', 'generatePaymentReceipt');                              //
        // Route::post('list-message', 'getIndependentComment');                                           //
        Route::post('application/edit', 'editWaterAppliction');                                          //
    });

    /**
     * | Created on : 10-02-2023
     * | Created By : Sam kerketta
     * |-------------- Water transaction and Payment related ---------------|
     */
    Route::controller(WaterPaymentController::class)->group(function () {
        # Consumer And Citizen Transaction Operation
        // Route::post('get-consumer-payment-history', 'getConsumerPaymentHistory');
        Route::post('consumer/get-payment-history', 'getConsumerPaymentHistory');
        Route::post('generate-payment-receipt', 'generatePaymentReceipt');
    });
});

/**
 * | Created On:09-12-2022 
 * | Created by:Sandeep Bara
 * | ------------------- Water Connection / mobile ------------------------ |
 */
Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {
    Route::controller(WaterApplication::class)->group(function () {
        // Route::match(["get", "post"], 'apply', 'applyApplication');
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
