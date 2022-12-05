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
        Route::post('getPropertyByHolding', 'validateHoldingNo');
        Route::post('updateApplicationById', 'updateLicenseBo');
        Route::post('updateBasicDtl', 'updateBasicDtl');
        Route::match(["get", "post"],'documentUpload/{id}', 'documentUpload');
        Route::match(["get", "post"],'documentVirify/{id}', 'documentVirify');
        Route::get('getLicenceDtl/{id}', 'getLicenceDtl');
        Route::post('getDenialDetails',"getDenialDetails");
        Route::post('searchLicense', 'searchLicence');
        Route::post('getApplicationList', 'readApplication');
        Route::post('escalate', 'postEscalate');
        Route::post('inbox', 'inbox');
        Route::post('outbox', 'outbox');
        Route::post('postNext', 'postNextLevel');
        Route::post('postComment', 'addIndependentComment');
        Route::post('pay', 'PaymentCounter');
        Route::match(["get", "post"],'applyDenail', 'applyDenail');
        Route::match(["get", "post"],'denialInbox', 'denialInbox');
        Route::match(["get", "post"],'denialview/{id}/{mailId}', 'denialview');
        Route::post('approvedApplication', 'approvedApplication');
        Route::post('reports', 'reports');
        Route::post('getComment', 'readIndipendentComment');
    });
});
Route::controller(ApplyApplication::class)->group(function () {    
    Route::get('paymentRecipt/{id}/{transectionId}', 'paymentRecipt');
    Route::get('provisionalCertificate/{id}', 'provisionalCertificate');
    Route::get('licenceCertificate/{id}', 'licenceCertificate');
});