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
        Route::get('get-connection-type', 'getConnectionType');                                         //05         // Get Water Connection Type Details mstr
        Route::get('get-connection-through', 'getConnectionThrough');                                   //06        // Get Water Connection Through Details mstr
        Route::get('get-property-type', 'getPropertyType');                                             //07        // Get Property Type Details mstr
        Route::get('get-owner-type', 'getOwnerType');                                                   //08        // Get Owner Type Details mstr
        Route::get('get-ward-no', 'getWardNo');                                                         //09        // Get Ward No According to Saf or Holding Details mstr

        # water Workflow
        Route::post('water-inbox', 'waterInbox');                                                       // 
        Route::post('water-outbox', 'waterOutbox');                                                     //
        Route::post('post-next-level','postNextLevel');                                                 //
        Route::post('get-applications-details','getApplicationsDetails');                               //
        Route::post('water-special-inbox','waterSpecialInbox');                                         //
        Route::post('post-escalate','postEscalate');                                                    //
        Route::post('get-water-doc-details','getWaterDocDetails');                                      //
        Route::post('water-doc-status','waterDocStatus');                                               //
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
        Route::post('documentUpload', 'documentUpload');                                                //13
        Route::post('getUploadedDoc', 'getUploadDocuments');                                            //14
    });
});
Route::controller(WaterApplication::class)->group(function () {
    Route::get('paymentRecipt/{id}/{transectionId}', 'paymentRecipt');                                  //15
    Route::post('cargeCal', 'calWaterConCharge');                                                       //16
    Route::post('consumerChargeCal', 'calConsumerDemand');                                              //17
});
Route::controller(WaterConsumer::class)->group(function () {
    Route::post('consumerChargeCal', 'calConsumerDemand');                                              //18        
});
