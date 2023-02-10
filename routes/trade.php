<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Trade\TradeApplication;
use App\Http\Controllers\Trade\TradeCitizenController;

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
        Route::post("getApplyData", "getApplyData");
        Route::post('apply', 'applyApplication');
        Route::post('getCharge', 'paybleAmount');
        Route::post('getPropertyByHolding', 'validateHoldingNo');
        Route::post('updateApplicationById', 'updateLicenseBo');
        Route::post('updateBasicDtl', 'updateBasicDtl');

        Route::post('get-doc-list', 'getDocList');
        Route::post('upload-document', 'uploadDocument');
        Route::post('get-upload-documents', 'getUploadDocuments');

        // Route::match(["get", "post"], 'documentUpload/{id}', 'documentUpload');

        // Route::post('upload-document', 'documentUpload');        //old
        Route::post('getUploadDocuments', 'getUploadDocuments');
        Route::match(["get", "post"], 'documentVerify/{licenceId}', 'documentVirify');
        Route::post('getLicenceDtl', 'getLicenceDtl');
        Route::post('getDenialDetails', "getDenialDetails");
        Route::post('searchLicense', 'searchLicence');
        Route::post('getApplicationList', 'readApplication');
        Route::post('escalate', 'postEscalate');
        Route::post('postBtc', 'backToCitizen');
        Route::post('specialInbox', 'specialInbox');
        Route::post('btcInbox', 'btcInbox');
        Route::post('inbox', 'inbox');
        Route::post('outbox', 'outbox');
        Route::post('postNext', 'postNextLevel');
        Route::post('approveReject', 'approveReject');
        Route::post('postComment', 'addIndependentComment');
        Route::post('pay', 'PaymentCounter');
        Route::match(["get", "post"], 'applyDenail', 'applyDenail');
        Route::match(["get", "post"], 'denialInbox', 'denialInbox');
        Route::match(["get", "post"], 'denialview/{id}/{mailId}', 'denialview');
        Route::post('approvedApplication', 'approvedApplication');
        Route::post('reports', 'reports');
        Route::post('getComment', 'readIndipendentComment');
        #------------citizenApplication---------------------        
    });
    Route::controller(TradeNoticeController::class)->group(function(){
        Route::post('applyDenail', 'applyDenail');
        Route::post('denialInbox', 'denialInbox');
        Route::post('denialview/{id}/{mailId}', 'denialview');
    });
    Route::controller(TradeCitizenController::class)->group(function () {
        Route::post('citizenGetWardList', "getWardList");               #id = c1
        Route::post('citizenApply', 'applyApplication');                #id = c2
        Route::post('citizenGetDenialDetails', "getDenialDetails");     #id = c3        
        Route::post('payOnline', 'handeRazorPay');                      #id = c4 
        Route::post('conformRazorPayTran', 'conformRazorPayTran');      #id = c5 
        Route::post('citizenApplication', 'citizenApplication');        #id = c6
        Route::post('citizenApplicationById', 'readCitizenLicenceDtl'); #id = c7
        // Route::post('expired-licence', 'expiredLicence');
        Route::post('list-renewal', 'renewalList');
        Route::post('list-amendment', 'amendmentList');
        Route::post('list-surrender', 'surrenderList');
    });
});

Route::controller(TradeApplication::class)->group(function () {
    Route::get('paymentReceipt/{id}/{transectionId}', 'paymentReceipt');
    Route::get('provisionalCertificate/{id}', 'provisionalCertificate');
    Route::get('licenceCertificate/{id}', 'licenceCertificate');
});
