<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Trade\ApplyApplication;

/**
 * | Created On-06-10-2022 
 * | Created For-The Routes defined for the Water Usage Charge Management System Module
 * | Created By-SandeepBara
 */

Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {
    /**
     *  -----------------------------------------------------------------
     * |                TRADE MODULE                                      |
     *  ------------------------------------------------------------------  
     * Created on- 06-10-2022
     * Created By- Sandeep Bara
     *  
     */
    Route::controller(ApplyApplication::class)->group(function () {        
        Route::match(["get", "post"], 'apply/{applicationType}/{id?}', 'applyApplication');
        Route::post('getCharge', 'paybleAmount');
        Route::post('getPropertyByHolding', 'validate_holding_no');
        Route::post('updateBasicDtl', 'updateBasicDtl');
        Route::get('getLicenceDtl/{id}', 'getLicenceDtl');
        Route::post('searchLicense', 'searchLicence');
        Route::post('inbox', 'inbox');
        Route::post('outbox', 'outbox');
        Route::post('postNext', 'postNextLevel');
        Route::post('pay', 'procidToPaymentCounter');
        Route::match(["get", "post"],'applyDenail', 'applyDenail');
        Route::match(["get", "post"],'denialInbox', 'denialInbox');
        Route::match(["get", "post"],'denialview/{id}/{mailId}', 'denialview');
    });
});
Route::controller(ApplyApplication::class)->group(function () {    
    Route::get('paymentRecipt/{id}/{transectionId}', 'paymentRecipt');
    Route::get('provisionalCertificate/{id}', 'provisionalCertificate');
    Route::get('licenceCertificate/{id}', 'licenceCertificate');
});