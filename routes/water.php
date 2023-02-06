<?php

use App\Http\Controllers\Water\NewConnectionController;
use App\Http\Controllers\water\WaterApplication;
use App\Http\Controllers\Water\WaterConsumer;
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
    Route::resource('crud/new-connection', NewConnectionController::class);                             //01

    Route::controller(NewConnectionController::class)->group(function () {
        Route::post('user-water-connection-charges', 'getUserWaterConnectionCharges');                  //02                           // Get Water Connection Charges of Logged In User
        Route::post('applicant-document-upload', 'applicantDocumentUpload');                            //03        // User Document Upload
        Route::post('water-payment', 'waterPayment');                                                   //04         // Water Payment
    });

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
        Route::post('list-inbox', 'waterInbox');                                                        // 
        Route::post('list-outbox', 'waterOutbox');                                                      //
        Route::post('post-next-level', 'postNextLevel');                                                //
        Route::post('get-pending-application-by-id', 'getApplicationsDetails');                         //
        Route::post('list-special-inbox', 'waterSpecialInbox');                                         //
        Route::post('escalate', 'postEscalate');                                                        //       
        Route::post('approval-rejection-application', 'approvalRejectionWater');                        //
        Route::post('post-message', 'commentIndependent');                                              //
        Route::post('approved-water-applications', 'approvedWaterApplications');                        //       
        Route::post('list-field-verified-inbox', 'fieldVerifiedInbox');                                 //
        Route::post('verification-field', 'fieldVerification');                                         //
        Route::post('generate_payment_receipt', 'generatePaymentReceipt');                              //
        Route::post('back_to_citizen', 'backToCitizen');                                                //
        Route::post('list_btc_inbox', 'btcInbox');                                                      //
        Route::Post('delete_application', 'deleteWaterApplication');                                    //       
        Route::post('get_application_details', 'getApplicationDetails');                                //
        Route::post('upload_document', 'uploadWaterDoc');                                               //
        Route::post('get_upload_documents', 'getUploadDocuments');                                      //
        Route::post('list_doc_to_upload', 'getDocToUpload');                                            //

        Route::post('get-related-saf-holding', 'getSafHoldingDetails');
        Route::post('final-submit-application', 'finalSubmitionApplication');
        Route::post('search-water-consumer', 'searchWaterConsumer');
        Route::post('search-active-applictaions', 'getActiveApplictaions');

        Route::post('getDocList', 'getDocList');
        Route::post('try', 'try');
        // Route::post('list-doc', 'getWaterDocDetails');                                                  //
        // Route::post('verify-doc', 'waterDocStatus');                                                    //
        // Route::post('list-message', 'getIndependentComment');                                           //
        // Route::post('edit_water_details', 'editWaterDetails');                                          //
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
        // Route::post('get_doc_to_upload', 'documentUpload');                                             //13
        // Route::post('getUploadedDoc', 'getUploadDocuments');                                            //14
    });
});
Route::controller(WaterApplication::class)->group(function () {
    Route::post('payment_recipt', 'paymentRecipt');                                  //15
    Route::post('cargeCal', 'calWaterConCharge');                                                       //16
    Route::post('consumerChargeCal', 'calConsumerDemand');                                              //17
});
Route::controller(WaterConsumer::class)->group(function () {
    Route::post('consumerChargeCal', 'calConsumerDemand');                                              //18        
});
