<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Trade\TradeApplication;

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
    Route::controller(TradeApplication::class)->group(function () {        
        Route::match(["get", "post"], 'apply/{applicationType}/{id?}', 'applyApplication');
        Route::post('getCharge', 'paybleAmount');
        Route::post('getPropertyByHolding', 'validateHoldingNo');
        Route::post('updateApplicationById', 'updateLicenseBo');
        Route::post('updateBasicDtl', 'updateBasicDtl');
        Route::match(["get", "post"],'documentUpload/{id}', 'documentUpload');
        Route::get('getUploadDocuments/{id}', 'getUploadDocuments');
        Route::match(["get", "post"],'documentVerify/{licenceId}', 'documentVirify');
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
        Route::post('payOnline', 'handeRazorPay');
        Route::match(["get", "post"],'applyDenail', 'applyDenail');
        Route::match(["get", "post"],'denialInbox', 'denialInbox');
        Route::match(["get", "post"],'denialview/{id}/{mailId}', 'denialview');
        Route::post('approvedApplication', 'approvedApplication');
        Route::post('reports', 'reports');
        Route::post('getComment', 'readIndipendentComment');
        #------------citizenApplication---------------------
        Route::get('citizenApplication', 'citizenApplication');
        Route::get('citizenApplication/{id}', 'readCitizenLicenceDtl');
        Route::post('conformRazorPayTran', 'conformRazorPayTran');
    });
});
Route::controller(TradeApplication::class)->group(function () {    
    Route::get('paymentReceipt/{id}/{transectionId}', 'paymentReceipt');
    Route::get('provisionalCertificate/{id}', 'provisionalCertificate');
    Route::get('licenceCertificate/{id}', 'licenceCertificate');
});