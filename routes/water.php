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

// Citizen View Water Screen For Mobile 
// Route::controller(NewConnectionController::class)->group(function () {
//     Route::get('get-connection-type', 'getConnectionType');
//     Route::get('get-connection-through', 'getConnectionThrough');
//     Route::get('get-property-type', 'getPropertyType');
//     Route::get('get-owner-type', 'getOwnerType');
//     Route::get('get-ward-no', 'getWardNo');
// });

Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {
    /**
     * | Created On-07-10-2022 
     * | Created by-Anshu Kumar
     * | ------------------- Apply New Water Connection ------------------------ |
     */
    Route::resource('crud/new-connection', NewConnectionController::class);

    Route::controller(NewConnectionController::class)->group(function () {
        Route::post('user-water-connection-charges', 'getUserWaterConnectionCharges');                                          // Get Water Connection Charges of Logged In User
        Route::post('user-water-connection-charges', 'getUserWaterConnectionCharges');                           // Get Water Connection Charges of Logged In User
        Route::post('applicant-document-upload', 'applicantDocumentUpload');                                     // User Document Upload
        Route::post('water-payment', 'waterPayment');                                                            // Water Payment
    });

    // req for the citizen
    Route::controller(NewConnectionController::class)->group(function () {
        Route::get('get-connection-type', 'getConnectionType');
        Route::get('get-connection-through', 'getConnectionThrough');
        Route::get('get-property-type', 'getPropertyType');
        Route::get('get-owner-type', 'getOwnerType');
        Route::get('get-ward-no', 'getWardNo');
    });
});
