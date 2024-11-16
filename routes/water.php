<?php

use App\Http\Controllers\Water\ConsumerActionRequest;
use App\Http\Controllers\Water\NewConnectionController;
use App\Http\Controllers\Water\WaterApplication;
use App\Http\Controllers\Water\WaterConsumer;
use App\Http\Controllers\Water\WaterConsumerWfController;
use App\Http\Controllers\Water\WaterPaymentController;
use App\Http\Controllers\Water\WaterReportController;
use App\Http\Controllers\Water\WaterMasterController;
use App\Repository\Water\Concrete\WaterNewConnection;
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

Route::group(['middleware' => ['json.response', 'auth_maker']], function () {
    /**
     * | Created On-08-10-2022 
     * | Updated by-Sam kerketta
     * | ------------------- Apply New Water Connection ------------------------ |
     */
    Route::resource('application/apply-new-connection', NewConnectionController::class);                //01
    /**
     * | Created On:08-11-2022 
     * | Created by:Sam Kerketta
     * | ------------------- Water Connection / mobile ------------------------ |
     */
    Route::controller(NewConnectionController::class)->group(function () {
        # Workflow
        Route::post('inbox', 'waterInbox');                                                             // Workflow
        Route::post('outbox', 'waterOutbox');                                                           // Workflow
        Route::post('post-next-level', 'postNextLevel');                                                // Workflow
        Route::post('workflow/application/get-by-id', 'getApplicationsDetails');                        // Workflow
        Route::post('special-inbox', 'waterSpecialInbox');                                              // Workflow
        Route::post('escalate', 'postEscalate');                                                        // Workflow                     
        Route::post('application/approval-rejection', 'approvalRejectionWater');                        // Workflow
        Route::post('comment-independent', 'commentIndependent');                                       // Workflow
        Route::post('field-verified-inbox', 'fieldVerifiedInbox');                                      // Workflow
        Route::post('back-to-citizen', 'backToCitizen');                                                // Workflow
        Route::post('btc-inbox', 'btcInbox');                                                           // Workflow
        Route::post('workflow/get-doc-list', 'getDocList');                                             // Workflow
        Route::post('doc-verify-reject', 'docVerifyRejects');                                           // Workflow
        Route::post('upload-document', 'uploadWaterDoc');                                               // Workflow/Citizen
        Route::post('get-upload-documents', 'getUploadDocuments');                                      // Workflow/Citizen  

        # Admin / Citizen view
        Route::post('application/delete', 'deleteWaterApplication');                                    // Citizen     
        Route::post('application/get-by-id', 'getApplicationDetails');                                  // Citizen
        Route::post('citizen/get-doc-list', 'getDocToUpload');                                          // Citizen  
        Route::post('application/edit', 'editWaterAppliction');                                         // Citizen/Admin
        Route::post('search-holding-saf', 'getSafHoldingDetails');                                      // Admin
        Route::post('application/search', 'getActiveApplictaions');                                     // Admin
        Route::post('admin/application/get-details-by-id', 'getApplicationDetailById');                 // Admin
        Route::post('admin/application/list-details-by-date', 'listApplicationBydate');                 // Admin
        Route::post('search-consumer', 'searchWaterConsumer');                                          // Admin/Consumer   
        Route::post('consumer/get-listed-details', 'approvedWaterApplications');                        // Consumer 

        # Site Inspection
        Route::post('admin/search-application', 'searchApplicationByParameter');                        // Admin
        Route::post('admin/application/save-inspection-date', 'saveInspectionDateTime');                // Workflow/Admin
        Route::post('admin/application/site-inspection-details', 'getSiteInspectionDetails');           // Workflow/Admin
        Route::post('admin/application/cancel-inspection-scheduling', 'cancelSiteInspection');          // Workflow/Admin
        Route::post('admin/application/je-site-details', 'getJeSiteDetails');                           // Workflow/Admin
        Route::post('admin/application/online-technical-inspection', 'onlineSiteInspection');           // Workflow
        Route::post('admin/application/technical-inspection-details', 'getTechnicalInsDetails');        // Workflow
    });

    /**
     * | Created on : 10-02-2023
     * | Created By : Sam kerketta
     * |-------------- Water transaction and Payment related ---------------|
     */
    Route::controller(WaterPaymentController::class)->group(function () {
        # Consumer And Citizen Transaction Operation
        Route::post('master/get-listed-details', 'getWaterMasterData');                                 // Admin/ Citizen
        Route::post('consumer/get-payment-history', 'getConsumerPaymentHistory');                       // Consumer
        Route::post('admin/application/generate-payment-receipt', 'generateOfflinePaymentReceipt');     // Citizen / Admin
        Route::post('consumer/calculate-month-demand', 'callDemandByMonth');                            // Admin/Consumer
        Route::post('application/payment/get-payment-history', 'getApplicationPaymentHistory');         // Admin/Consumer
        Route::post('consumer/offline-demand-payment', 'offlineDemandPayment');                         // Consumer
        Route::post('application/payment/offline/pay-connection-charge', 'offlineConnectionPayment');   // Admin
        Route::post('consumer/demand/generate-payment-receipt', 'generateDemandPaymentReceipt');        // Admin/ Citizen
        Route::post('consumer/online-demand-payment', 'initiateOnlineDemandPayment');                   // Citizen
        Route::post('citizen/payment-history', 'paymentHistory');                                       // Citizen  
        Route::post('consumer/water-user-charges', 'getWaterUserCharges');                              // Admin / Citizen
        Route::post('consumer/online-request-payment', 'initiateOnlineConRequestPayment');              // Citizen
        Route::post('consumer/offline-request-payment', 'offlineConReqPayment');                        // Admin

        # Site inspection 
        Route::post('site-verification/save-site-details', 'saveSitedetails');                          // Admin
    });

    /**
     * | Created On : 11-02-2023
     * | Created By : Sam kerketta
     * |------------- Water Consumer and Related -------------|
     */
    Route::controller(WaterConsumer::class)->group(function () {
        Route::post('consumer/list-demand', 'listConsumerDemand');                                      // Consumer
        Route::post('admin/consumer/generate-demand', 'saveGenerateConsumerDemand');                    // Admin
        Route::post('admin/consumer/save-connection-meter', 'saveUpdateMeterDetails');                  // Admin
        Route::post('admin/consumer/get-meter-list', 'getMeterList');                                   // Admin
        Route::post('consumer/caretaken-connections', 'viewCaretakenConnection');                       // Citiizen
        Route::post('consumer/calculate/meter-fixed-reading', 'calculateMeterFixedReading');            // Admin
        Route::post('consumer/self-generate-demand', 'selfGenerateDemand');                             // Citizen
        Route::post('consumer/get-connection-list', 'getConnectionList');

        # Unfinished API
        Route::post('admin/consumer/add-fixed-rate', 'addFixedRate');               // Here             // Admin / Not used
        Route::post('consumer/generate-memo', 'generateMemo');                      // Here             // Admin / Citizen
        Route::post('consumer/search-fixed-connections', 'searchFixedConsumers');   // Here             // Admin / Not used
        Route::post('consumer/add-advance', 'addAdvance');                                              // Admin

        # Testing
        Route::post('check-doc', 'checkDoc'); // testing document service

        # Deactivation // Arshad
        Route::post('apply-water-disconnection', 'applyWaterDisconnection');                //<----remove
        Route::post('admin/consumer/demand-deactivation', 'consumerDemandDeactivation');    // Here         // Admin / Not used

        # Ferrul Cleaning and Pipe shifting // Arshad
        Route::post('applywater-ferule-cleaning', 'applyConsumerRequest');                  //<---- cheange the route name Admin / Citizen

        //written by Prity Pandey
        Route::post('admin/consumer/apply-deactivation', 'applyDeactivation');
        Route::post('get_consumer_details_by_id', 'getByApplicationId');
        //Route::post('consumer_doc_upload', 'uploadWaterDocForDeactivation');  
        //Route::post('get/consumer_doc', 'getDocList'); 
        Route::post('applicant/search', 'searchApplication');
    });


    /**
     * | Created On : 15-07-2023
     * | Created By : Sam kerketta
     * |------------ Water Consumer Workflow -------------|
     */
    Route::controller(WaterConsumerWfController::class)->group(function () {
        # Workflow 
        Route::post('consumer/req/list-req-docs', 'listDocToUpload');                               // Here
        Route::post('consumer/req/get-worklfow-by-id', 'getWorkflow');                       // Here

        # Consuemr Request View Api // Arshad
        Route::post('get-details-applications', 'getConApplicationDetails');                        // Admin / Changes             
        Route::post('get-details-disconnections', 'getRequestedApplication');                       // Citizen / Changes the route name
        Route::post('consumer/req/approval-rejection', 'consumerApprovalRejection');
        //written by prity pandey 
        Route::post('consumer/req/inbox', 'consumerInbox');                                         // Workflow
        Route::post('consumer/req/outbox', 'consumerOutbox');
        Route::post('deactivation/back-to-citizen', 'backToCitizen');
        Route::post('consumer/deactivation-escalate-inbox', 'specialInbox');
        Route::post('consumer/deactivation-btc-inbox', 'btcInbox');
        Route::post('consumer/deactivation-worklfow-by-id', 'getConsumerDetails');
        Route::post('consumer/deactivation-get-doc-list', 'getDocList');
        Route::post('consumer/deactivation-upload-documents', 'uploadDocuments');
        Route::post('consumer/deactivation-upload-documents_view', 'getUploadDocuments');
        Route::post('consumer/deactivation-documents-verify', 'documentVerify');
        Route::post('consumer/deactivation-post-next-level', 'postNextLevelRequestV1');
        Route::post('consumer/deactivation-approve-rejet', 'approveReject');
        //Route::post('consumer_doc-verify-reject', 'docVerifyRejects');
        //Route::post('consumer/req/post-next-level', 'consumerPostNextLevel');
        //Route::post('consumer/req/approval-rejection', 'consumerDeactivationApprovalRejection');
    });

    /**
     * | Created On : 17-04-2023
     * | Created By : Sam kerketta
     * |------------- Water Reports -------------|
     */
    Route::controller(WaterReportController::class)->group(function () {
        Route::post('consumer/report/list-ward-dcb', 'wardWiseDCB');                                    //01
        Route::post('consumer/report/dcb-pie-chart', 'dcbPieChart');                                    //02
        Route::post('report-cosumer', 'consumerReport');                                                //03
        Route::post('connection-collection', 'connectionCollection');                                   //04
        Route::post('new-connection-report', 'newConnectioReport');                                     //05
        Route::post('level-wise-report', 'levelWiseReport');                                           //05
    });


    /**
     * | Created On:09-12-2022 
     * | Created by:Sandeep Bara
     * | Modified by: Sam kerketta
     * | Modified on: 11-01-2023
     * | ------------------- Water Connection / mobile ------------------------ |
     */
    Route::controller(WaterApplication::class)->group(function () {
        Route::post('citizenApplications', 'getCitizenApplication');                                    //10
        Route::post('Razorpay-Orderid', 'handeRazorPay');                                               //11
        Route::post('getTranNo', 'readTransectionAndApl');                                              //12

        # Dashbording Api
        Route::post('admin/application/dashboard-data', 'getJskAppliedApplication');                    //13
        Route::post('admin/workflow/dashboard-data', 'workflowDashordDetails');                         //14
    });
    /**
     * |created by - Arshad Hussain
     * |created on - 15/03/2024
     * |for master crud operation 
     */
    Route::controller(WaterMasterController::class)->group(function () {
        Route::post('create-water-prop-type', 'createWaterPropTypeMstr');
        Route::post('get-water-prop-type', 'getAllData');
        Route::post('getby-id-water-prop-type', 'getDataById');
        Route::post('delete-water-prop-type', 'activeDeactiveById');
        Route::post('update-water-prop-type', 'updateById');
        #===crud operation for water pipeline type===#
        Route::post('create-water-pipeline-type', 'createWaterPipelineType');
        Route::post('get-water-pipeline-type', 'getAllPipeline');
        Route::post('getby-id-water-pipeline-type', 'getDataId');
        Route::post('delete-water-pipeline-type', 'dataActiveDeactive');
        Route::post('update-water-pipeline-type', 'updateDataById');
    });

    /**
     * create by - Sandeep Bara
     * date      - 2024-04-24
     * =========== this is for consumer disconnection=============
     */
    Route::controller(ConsumerActionRequest::class)->group(function () {
        Route::post('request/discon/sedule-inspection', 'setDisconnectionSitInspection');
        Route::post("request/discon/getInspectinMaster", "getSiteInspectionCompar");
        Route::post('request/discon/sedule-cancel', 'cancelDisconnectionSitInspection');
        Route::post("request/discon/inspection", "updateSiteInspection");
        Route::post("request/discon/get-inspection-dtls", "getInspectionData");
    });
});
Route::controller(WaterApplication::class)->group(function () {
    Route::post('cargeCal', 'calWaterConCharge');                                                       //16

});
Route::controller(WaterConsumer::class)->group(function () {
    Route::post('consumerChargeCal', 'calConsumerDemand');                                              //18        
});


//Not in use

Route::controller(WaterConsumerWfController::class)->group(function () {
    # Workflow 
    Route::post('consumer/req/list-req-docs', 'listDocToUpload');

    Route::post('consumer/req/doc-verify-reject', 'consumerDocVerifyReject');                   // Here   

    Route::post('consumer/req/get-upload-documents', 'getConsumerDocs');
});



Route::controller(WaterApplication::class)->group(function () {
    Route::post('update-applications', 'updateWaterApplication');       // Here 
    Route::post('consumerChargeCal', 'calConsumerDemand');                                              //17
});

Route::controller(WaterNewConnection::class)->group(function () {
    Route::post('test', 'razorPayResponse');       // Here 
});
