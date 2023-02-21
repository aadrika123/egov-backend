<?php

use App\Http\Controllers\Trade\ReportControlle;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Trade\TradeApplication;
use App\Http\Controllers\Trade\TradeCitizenController;
use App\Http\Controllers\Trade\TradeNoticeController;
use App\Http\Controllers\Trade\ReportController;

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
        // Route::post("application/new-license", "getMstrForNewLicense");
        // Route::post("application/renewal", "getMstrForRenewal");
        // Route::post("application/amendment", "getMstrForAmendment");
        // Route::post("application/surrender", "getMstrForSurender");

        // Route::post('apply', 'applyApplication');
        Route::post('application/add', 'applyApplication');

        // Route::post('getCharge', 'paybleAmount');
        Route::post('application/get-demand', 'paybleAmount');

        // Route::post('getPropertyByHolding', 'validateHoldingNo');
        Route::post('property/by-holding', 'validateHoldingNo');

        // Route::post('updateApplicationById', 'updateLicenseBo');
        Route::post('application/edit-by-id', 'updateLicenseBo');

        // Route::post('updateBasicDtl', 'updateBasicDtl');
        Route::post('application/edit', 'updateBasicDtl');

        // Route::post('get-doc-list', 'getDocList');
        Route::post('application/doc-list', 'getDocList');

        // Route::post('upload-document', 'uploadDocument');
        Route::post('application/upload-document', 'uploadDocument');

        // Route::post('get-upload-documents', 'getUploadDocuments');
        Route::post('appliction/documents', 'getUploadDocuments');

        // Route::post('getUploadDocuments', 'getUploadDocuments');
        // Route::match(["get", "post"], 'documentVerify/{licenceId}', 'documentVirify');

        // Route::post('getLicenceDtl', 'getLicenceDtl');
        Route::post('application/dtl-by-id', 'getLicenceDtl');

        // Route::post('getDenialDetails', "getDenialDetails");
        Route::post('notice/details', "getDenialDetails");

        // Route::post('searchLicense', 'searchLicence');
        Route::post('application/search-for-renew', 'searchLicence');

        // Route::post('getApplicationList', 'readApplication');
        Route::post('application/list', 'readApplication');

        // Route::post('escalate', 'postEscalate');
        Route::post('application/escalate', 'postEscalate');

        // Route::post('postBtc', 'backToCitizen');
        Route::post('application/btc', 'backToCitizen');

        // Route::post('specialInbox', 'specialInbox');
        Route::post('application/escalate-inbox', 'specialInbox');
        
        // Route::post('btcInbox', 'btcInbox');
        Route::post('application/btc-inbox', 'btcInbox');

        // Route::post('inbox', 'inbox');
        Route::post('application/inbox', 'inbox');

        // Route::post('outbox', 'outbox');
        Route::post('application/outbox', 'outbox');

        // Route::post('postNext', 'postNextLevel');
        Route::post('application/post-next', 'postNextLevel');

        // Route::post('approveReject', 'approveReject');
        Route::post('application/approve-reject', 'approveReject');

        // Route::post('postComment', 'addIndependentComment');
        Route::post('application/independent-comment', 'addIndependentComment');

        // Route::post('pay', 'PaymentCounter');
        Route::post('application/pay-charge', 'PaymentCounter');

        // Route::post('approvedApplication', 'approvedApplication');
        Route::post('application/approved-list', 'approvedApplication');

        // Route::post('getComment', 'readIndipendentComment');
        Route::post('application/get-independent-comment', 'readIndipendentComment');

        Route::post('reports', 'reports');        
               
    });

    Route::controller(TradeNoticeController::class)->group(function(){
        // Route::post('applyDenail', 'applyDenail');
        Route::post('notice/add', 'applyDenail');

        // Route::post('noticeInbox', 'inbox');
        Route::post('notice/inbox', 'inbox');

        // Route::post('noticeOutbox', 'outbox');
        Route::post('notice/outbox', 'outbox');

        // Route::post('noticeBtcInbox', 'btcInbox');
        Route::post('notice/btc-inbox', 'btcInbox');

        // Route::post('noticepostNext', 'postNextLevel');
        Route::post('notice/post-next', 'postNextLevel');

        // Route::post('noticeApproveReject', 'approveReject');
        Route::post('notice/approve-reject', 'approveReject');

        // Route::post('denialview', 'denialview');
        Route::post('notice/view', 'denialview');
    });

    #------------citizenApplication--------------------- 
    Route::controller(TradeCitizenController::class)->group(function () {
        // Route::post('citizenGetWardList', "getWardList");               #id = c1
        Route::post('application/citizen-ward-list', "getWardList");               #id = c1

        // Route::post('citizenApply', 'applyApplication');                #id = c2
        Route::post('application/citizen-add', 'applyApplication');                #id = c2
        
        // Route::post('citizenGetDenialDetails', "getDenialDetails");     #id = c3        
        Route::post('notice/citizen-details', "getDenialDetails");     #id = c3 

        // Route::post('payOnline', 'handeRazorPay');                      #id = c4
        Route::post('application/pay-razorpay-charge', 'handeRazorPay');           #id = c4

        // Route::post('conformRazorPayTran', 'conformRazorPayTran');      #id = c5
        Route::post('application/conform-razorpay-tran', 'conformRazorPayTran');      #id = c5

        // Route::post('citizenApplication', 'citizenApplication');        #id = c6
        Route::post('application/citizen-application', 'citizenApplication');        #id = c6

        // Route::post('citizenApplicationById', 'readCitizenLicenceDtl'); #id = c7
        Route::post('application/citizen-by-id', 'readCitizenLicenceDtl'); #id = c7

        // Route::post('list-renewal', 'renewalList');
        Route::post('application/renewable-list', 'renewalList');

        // Route::post('list-amendment', 'amendmentList');
        Route::post('application/amendable-list', 'amendmentList');

        // Route::post('list-surrender', 'surrenderList');
        Route::post('application/surrenderable-list', 'surrenderList');
    });
});

Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {
    Route::controller(ReportController::class)->group(function () {
        Route::post("application/collection-reports", "CollectionReports");
        Route::post("application/team-summary", "teamSummary");
        Route::post("application/valide-expire-list", "valideAndExpired");
    });
});

Route::controller(TradeApplication::class)->group(function () {
    Route::get('payment-receipt/{id}/{transectionId}', 'paymentReceipt');
    Route::get('provisional-certificate/{id}', 'provisionalCertificate');
    Route::get('license-certificate/{id}', 'licenceCertificate');
});
