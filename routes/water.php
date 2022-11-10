<?php

use App\Http\Controllers\Water\NewConnectionController;
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
     * | ------------------- Apply New Water Connection ------------------------ |
     */
    Route::resource('crud/new-connection', NewConnectionController::class);

    Route::controller(NewConnectionController::class)->group(function () {
        Route::post('user-water-connection-charges', 'getUserWaterConnectionCharges');                           // Get Water Connection Charges of Logged In User
        Route::post('user-water-connection-charges', 'getUserWaterConnectionCharges');                           // Get Water Connection Charges of Logged In User
        Route::post('applicant-document-upload', 'applicantDocumentUpload');                                     // User Document Upload
        Route::post('water-payment', 'waterPayment');                                                            // Water Payment
    });

    /**
     * | Created On:08-11-2022 
     * | Created by:Sam Kerketta
     * | ------------------- Water Connection / mobile ------------------------ |
     */

    // Citizen View Water Screen For Mobile 
    Route::controller(NewConnectionController::class)->group(function () {
        Route::get('get-connection-type', 'getConnectionType');                                                 // Get Water Connection Type Details mstr
        Route::get('get-connection-through', 'getConnectionThrough');                                           // Get Water Connection Through Details mstr
        Route::get('get-property-type', 'getPropertyType');                                                     // Get Property Type Details mstr
        Route::get('get-owner-type', 'getOwnerType');                                                           // Get Owner Type Details mstr
        Route::get('get-ward-no', 'getWardNo');                                                                 // Get Ward No According to Saf or Holding Details mstr
    });
});
