<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Property\ActiveSafController;
use App\Http\Controllers\Property\ActiveSafControllerV2;
use App\Http\Controllers\Property\ConcessionController;
use App\Http\Controllers\Property\SafCalculatorController;
use App\Http\Controllers\Property\CalculatorController;
use App\Http\Controllers\Property\DocumentOperationController;
use App\Http\Controllers\Property\ObjectionController;
use App\Http\Controllers\Property\PropertyDeactivateController;
use App\Http\Controllers\Property\RainWaterHarvestingController;
use App\Http\Controllers\Property\SafReassessmentController;
use App\Http\Controllers\Property\PropertyBifurcationController;
use App\Http\Controllers\Property\PropMaster;
use App\Http\Controllers\Property\PropertyDetailsController;
use App\Http\Controllers\property\ClusterController;
use App\Http\Controllers\Property\ConcessionDocController;
use App\Http\Controllers\Property\HoldingTaxController;
use App\Http\Controllers\Property\ReportController;
use App\Http\Controllers\Property\SafDocController;
use App\Http\Controllers\Property\ZoneController;

/**
 * | ---------------------------------------------------------------------------
 * | Property API Routes
 * | ---------------------------------------------------------------------------
 *  | Here is where you can register Property API routes for your application. These
   | routes are loaded by the RouteServiceProvider within a group which
   | is assigned the "api" middleware group. Enjoy building your API!
   | ---------------------------------------------------------------------------
   | Created By - Anshu Kumar
   | Created On - 11/10/2022
 */

/**
 * ----------------------------------------------------------------------------------------
 * | Property Module Routes
 * | Restructuring by - Anshu Kumar
 * | Property Module by Anshu Kumar from - 11/10/2022
 * ----------------------------------------------------------------------------------------
 */


// Inside Middleware Routes with API Authenticate 
Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {

  /**
   * | Route Outside the Middleware
   | Serial No : 
   */
  Route::controller(CalculatorController::class)->group(function () {
    Route::post('calculatePropertyTax', 'calculator');
    Route::post('review-calculation', 'reviewCalculation');       // Review for the Calculation
  });

  /**
   * | SAF
     | Serial No : 01
   */
  Route::controller(ActiveSafController::class)->group(function () {
    Route::get('saf/master-saf', 'masterSaf');                                                          // Get all master data in Saf(1)
    Route::post('saf/apply', 'applySaf');                                                               // Applying Saf Route(2)
    Route::post('saf/edit', 'editSaf');                                                                 // Edit Saf By Back Office(24)
    Route::get('saf/inbox', 'inbox');                                                                   // Saf Inbox(3)
    Route::post('saf/btc-inbox', 'btcInbox');                                                           // Saf Inbox for Back To citizen(23)
    Route::post('saf/field-verified-inbox', 'fieldVerifiedInbox');                                      // Field Verified Inbox (25)
    Route::get('saf/outbox', 'outbox');                                                                 // Saf Workflow Outbox and Outbox By search key(4)
    Route::post('saf-details', 'safDetails');                                                           // Saf Workflow safDetails and safDetails By ID(5)
    Route::post('saf/escalate', 'postEscalate');                                                        // Saf Workflow special and safDetails By id(6)
    Route::get('saf/escalate/inbox/{key?}', 'specialInbox');                                            // Saf workflow Inbox and Inbox By search key(7)
    Route::post('saf/independent-comment', 'commentIndependent');                                       // Independent Comment for SAF Application(8)
    Route::post('saf/post/level', 'postNextLevel');                                                     // Forward or Backward Application(9)
    Route::post('saf/approvalrejection', 'approvalRejectionSaf');                                       // Approval Rejection SAF Application(10)
    Route::post('saf/back-to-citizen', 'backToCitizen');                                                // Saf Application Back To Citizen(11)
    Route::post('saf/get-prop-byholding', 'getPropByHoldingNo');                                        // get Property (search) by ward no and holding no(12)
    Route::post('saf/generate-order-id', 'generateOrderId');                                            // Generate Order ID(14)
    Route::get('saf/prop-transactions', 'getPropTransactions');                                         // Get Property Transactions(17)
    Route::post('saf/site-verification', 'siteVerification');                                           // Ulb TC Site Verification(18)
    Route::post('saf/geotagging', 'geoTagging');                                                        // Geo Tagging(19)
    Route::post('saf/get-tc-verifications', 'getTcVerifications');                                      // Get TC Verifications  Data(20)
    Route::post('saf/proptransaction-by-id', 'getTransactionBySafPropId');                              // Get Property Transaction by Property ID or SAF id(22)
    Route::post('saf/get-demand-by-id', 'getDemandBySafId');                                            // Get the demandable Amount of the Property after payment done(26)
    // Route::post('saf/get-btc-fields', 'getBtcFields'); 
    Route::post('saf/verifications-comp', 'getVerifications');
    Route::post('saf/IndiVerificationsList', 'getSafVerificationList');
    Route::post('saf/static-saf-dtls', 'getStaticSafDetails');                                          // (27) Static SAf Details
  });

  /**
   * | SAF Demand and Property contollers
       | Serial No : 02
   */
  Route::controller(SafDocController::class)->group(function () {
    Route::post('saf/document-upload', 'docUpload');                                                    // Upload Documents for SAF (01)
    Route::post('saf/get-uploaded-documents', 'getUploadDocuments');                                    // View Uploaded Documents for SAF (02)
    Route::post('saf/get-doc-list', 'getDocList');                                                      // Get Document Lists(03)
    Route::post('saf/doc-verify-reject', 'docVerifyReject');                                            // Verify or Reject Saf Documents(04)
  });

  /**
   * | Property Calculator
       | Serial No : 04
   */
  Route::controller(SafCalculatorController::class)->group(function () {
    Route::post('saf-calculation', 'calculateSaf');
  });

  /**
   * | Property Deactivation
   * | Crated By - Sandeep Bara
   * | Created On- 19-11-2022 
       | Serial No : 05
   */
  Route::controller(PropertyDeactivateController::class)->group(function () {
    Route::post('searchByHoldingNo', "readHoldigbyNo");
    Route::post("get-prop-dtl-for-deactivation", "readPorertyById");
    Route::post('deactivationRequest', "deactivatProperty");
    Route::post('inboxDeactivation', "inbox");
    Route::post('outboxDeactivation', "outbox");
    Route::post('postNextDeactivation', "postNextLevel");
    Route::post('commentIndependentPrpDeactivation', "commentIndependent");
    Route::post('postEscalateDeactivation', "postEscalate");
    Route::post('getDocumentsPrpDeactivation', "getUplodedDocuments");
    Route::post('approve-reject-deactivation-request', "approvalRejection");
    Route::post('getDeactivationDtls', "readDeactivationReq");
  });

  /**
   * | PropertyBifurcation Process
   * | Crated By - Sandeep Bara
   * | Created On- 23-11-2022
     | Serial No : 06
   */
  Route::controller(PropertyBifurcationController::class)->group(function () {
    Route::post('searchByHoldingNoBi', "readHoldigbyNo");
    Route::match(["POST", "GET"], 'applyBifurcation/{id}', "addRecord");
    Route::post('bifurcationInbox', "inbox");
    Route::post('bifurcationOutbox', "outbox");
    Route::post('bifurcationPostNext', "postNextLevel");
    Route::get('getSafDtls/{id}', "readSafDtls");
    Route::match(["get", "post"], 'documentUpload/{id}', 'documentUpload');

    // Route::match(["get", "post"], 'safDocumentUpload/{id}', 'safDocumentUpload');
  });

  /**
   * | Property Concession
       | Serial No : 07
   */
  Route::controller(ConcessionController::class)->group(function () {
    Route::post('concession/apply-concession', 'applyConcession');                      //01                
    Route::post('concession/postHolding', 'postHolding');                               //02  
    Route::post('concession/inbox', 'inbox');                                           //03               // Concession Inbox 
    Route::post('concession/outbox', 'outbox');                                         //04               // Concession Outbox
    Route::post('concession/details', 'getDetailsById');                                //05               // Get Concession Details by ID
    Route::post('concession/escalate', 'escalateApplication');                          //06               // escalate application
    Route::post('concession/special-inbox', 'specialInbox');                            //07               // escalated application inbox
    Route::post('concession/btc-inbox', 'btcInbox');                                    //17               // Back To Citizen Inbox

    Route::post('concession/next-level', 'postNextLevel');                              //08               // Backward Forward Application
    Route::post('concession/approvalrejection', 'approvalRejection');                   //09               // Approve Reject Application
    Route::post('concession/backtocitizen', 'backToCitizen');                           //10               // Back To Citizen 
    Route::post('concession/owner-details', 'getOwnerDetails');                         //11

    Route::post('concession/comment-independent', 'commentIndependent');                //18               ( Citizen Independent comment and Level Pendings )
    Route::post('concession/get-doc-type', 'getDocType');
    Route::post('concession/doc-list', 'concessionDocList');                            //14
    Route::post('concession/upload-document', 'uploadDocument');
    Route::post('concession/get-uploaded-documents', 'getUploadedDocuments');
    Route::post('concession/doc-verify-reject', 'docVerifyReject');
  });

  /**
   * | Property Concession doc Controller
   * | Serial No : 16
   */
  Route::controller(ConcessionDocController::class)->group(function () {
    Route::post('concession/document-list', 'docList');                                //01
  });


  /**
   * | Property Objection
       | Serial No : 08
   */
  Route::controller(ObjectionController::class)->group(function () {
    Route::post('objection/apply-objection', 'applyObjection');           //01
    Route::get('objection/objection-type', 'objectionType');              //02                      
    Route::post('objection/owner-detailById', 'ownerDetailById');               //03
    Route::post('objection/assesment-details', 'assesmentDetails');       //04

    Route::post('objection/inbox', 'inbox');                              //05        //Inbox
    Route::post('objection/outbox', 'outbox');                            //06        //Outbox
    Route::post('objection/details', 'getDetailsById');                   //07
    Route::post('objection/post-escalate', 'postEscalate');               //08        // Escalate the application and send to special category
    Route::post('objection/special-inbox', 'specialInbox');               //09        // Special Inbox 
    Route::post('objection/next-level', 'postNextLevel');                 //10
    Route::post('objection/approvalrejection', 'approvalRejection');      //11
    Route::post('objection/backtocitizen', 'backToCitizen');              //12
    Route::post('objection/btc-inbox', 'btcInboxList');                   //18

    Route::get('objection/list', 'objectionList');                          //13
    Route::post('objection/list-id', 'objectionByid');                      //14

    Route::post('objection/comment-independent', 'commentIndependent');     //18
    Route::post('objection/doc-list', 'objectionDocList');                  //14
    Route::post('objection/upload-document', 'uploadDocument');             //19
    Route::post('objection/get-uploaded-documents', 'getUploadedDocuments');  //20
    Route::post('objection/add-members', 'addMembers');                     //21
    Route::post('objection/citizen-doc-list', 'citizenDocList');
    Route::post('objection/doc-verify-reject', 'docVerifyReject');
  });


  /**
   * | Calculator dashboardDate
       | Serial No : 10
   */
  Route::controller(CalculatorController::class)->group(function () {
    Route::post('get-dashboard', 'dashboardDate');
  });


  /**
   * | Rain water Harvesting
   * | Created By - Sam kerketta
   * | Created On- 22-11-2022
   * | Modified By - Mrinal Kumar
   * | Modification On- 10-12-2022
   * 
       | Serial No : 11
   */
  Route::controller(RainWaterHarvestingController::class)->group(function () {
    Route::get('get-wardmaster-data', 'getWardMasterData');                     //01
    Route::post('water-harvesting-application', 'waterHarvestingApplication');  //02
    // Route::post('get-harvesting-list', 'waterHarvestingList');                  //03
    // Route::post('harvesting-list-id', 'harvestingListById');                    //04
    // Route::post('harvesting-doc-id', 'harvestingDocList');                      //05
    // Route::post('harvesting-doc-upload', 'docUpload');                          //06
    // Route::post('harvesting-doc-status', 'docStatus');                          //07
    Route::post('harvesting/inbox', 'harvestingInbox');                         //08
    Route::post('harvesting/outbox', 'harvestingOutbox');                       //09
    Route::post('harvesting/next-level', 'postNextLevel');                      //10
    Route::post('harvesting/approval-rejection', 'finalApprovalRejection');     //11
    Route::post('harvesting/rejection', 'rejectionOfHarvesting');               //12
    Route::post('harvesting/details-by-id', 'getDetailsById');                  //13
    Route::post('harvesting/escalate', 'postEscalate');                         //14
    Route::post('harvesting/special-inbox', 'specialInbox');                    //15
    Route::post('harvesting/comment-independent', 'commentIndependent');        //16
    Route::post('harvesting/get-doc-list', 'getDocList');
    Route::post('harvesting/upload-document', 'uploadDocument');
    Route::post('harvesting/get-uploaded-documents', 'getUploadedDocuments');
    Route::post('harvesting/citizen-doc-list', 'citizenDocList');
    Route::post('harvesting/doc-verify-reject', 'docVerifyReject');
    Route::post('harvesting/field-verification-inbox', 'fieldVerifiedInbox');

    Route::post('harvesting/backtocitizen', 'backToCitizen');
    Route::post('harvesting/btc-inbox', 'btcInboxList');
    Route::post('harvesting/site-verification', 'siteVerification');
    Route::post('harvesting/get-tc-verifications', 'getTcVerifications');
  });

  /**
   * | Property Cluster
   * | Created By - Sam kerketta
   * | Created On- 23-11-2022 
       | Serial No : 12
   */
  Route::controller(ClusterController::class)->group(function () {

    #cluster data entry / Master
    Route::get('cluster/get-all-clusters', 'getAllClusters');
    Route::post('cluster/edit-cluster-details', 'editClusterDetails');
    Route::post('cluster/save-cluster-details', 'saveClusterDetails');
    Route::post('cluster/delete-cluster-data', 'deleteClusterData');
    Route::post('cluster/get-cluster-by-id', 'getClusterById');           // Remark
    # cluster maping
    Route::post('cluster/details-by-holding', 'detailsByHolding');
    Route::post('cluster/property-by-cluster', 'propertyByCluster');
    Route::post('cluster/save-holding-in-cluster', 'saveHoldingInCluster');
    Route::post('cluster/get-saf-by-safno', 'getSafBySafNo');
    Route::post('cluster/save-saf-in-cluster', 'saveSafInCluster');
  });

  /**
   * | Property Document Operation
       | Serial No : 13
   */
  Route::controller(DocumentOperationController::class)->group(function () {
    Route::post('get-all-documents', 'getAllDocuments');
  });

  /**
   * | poperty related type details form ref
       | Serial No : 14 
   */
  Route::controller(PropMaster::class)->group(function () {
    Route::get('prop-usage-type', 'propUsageType');
    Route::get('prop-const-type', 'propConstructionType');
    Route::get('prop-occupancy-type', 'propOccupancyType');
    Route::get('prop-property-type', 'propPropertyType');
    Route::get('prop-road-type', 'propRoadType');
  });

  /**
   * | Property Details
       | Serial No : 15
   */
  Route::controller(PropertyDetailsController::class)->group(function () {
    Route::post('get-filter-application-details', 'applicationsListByKey');        // 01
    Route::post('get-filter-property-details', 'propertyListByKey');              // 02
    Route::get('get-list-saf', 'getListOfSaf');                                   // 03
    Route::post('active-application/get-user-details', 'getUserDetails');         // 04
  });


  /**
    | Serial No : 17
   */
  Route::controller(ZoneController::class)->group(function () {
    Route::post('get-zone-byUlb', 'getZoneByUlb');        // 01

  });

  /**
   * | Calculation of Yearly Property Tax and generation of its demand
   * | Serial No-16 
   */
  Route::controller(HoldingTaxController::class)->group(function () {
    Route::post('generate-holding-demand', 'generateHoldingDemand');              // (01) Property/Holding Yearly Holding Tax Generation
    Route::post('get-holding-dues', 'getHoldingDues');                            // (02) Property/ Holding Dues
    Route::post('generate-prop-orderid', 'generateOrderId');                      // (03) Generate Property Order ID
  });

  /**
    | Serial No : 18
   */
  Route::controller(ActiveSafControllerV2::class)->group(function () {
    Route::post('saf/delete-citizen-saf', 'deleteCitizenSaf');        // 01
    Route::post('saf/edit-citizen-saf', 'editCitizenSaf');            // 02
    Route::post('saf/memo-receipt', 'memoReceipt');                   // 03
  });
});


/**
 * | SAF
     | Serial No : 01
 */

Route::controller(ActiveSafController::class)->group(function () {
  Route::post('saf/saf-payment', 'paymentSaf');                                                       // SAF Payment(15)
  Route::post('saf/calculate-by-saf-id', 'calculateSafBySafId');                                      // Calculate SAF By SAF ID(13)
  Route::post('saf/independent/generate-order-id', 'generateOrderId');                                // Generate Order ID(14)
  Route::post('saf/payment-receipt', 'generatePaymentReceipt');                                       // Generate payment Receipt(16)
});

/**
 * | Route Outside the Authenticated Middleware 
    Serial No : 18
 */
Route::controller(ActiveSafControllerV2::class)->group(function () {
  Route::post('search-holding', 'searchHolding');
});
/**
 * | Holding Tax Controller(Created By-Anshu Kumar)
   | Serial No-16
 */
Route::controller(HoldingTaxController::class)->group(function () {
  Route::post('payment-holding', 'paymentHolding');                                         // (04) Payment Holding
  Route::post('prop-payment-receipt', 'propPaymentReceipt');                                // (05) Generate Property Payment Receipt
  Route::post('independent/get-holding-dues', 'getHoldingDues');                            // (07) Property/ Holding Dues
  Route::post('independent/generate-prop-orderid', 'generateOrderId');                      // (08) Generate Property Order ID
  Route::post('prop-payment-history', 'propPaymentHistory');                                // (06) Property Payment History
  Route::post('prop-ulb-receipt', 'proUlbReceipt');                                         // (09) Property Ulb Payment Receipt
});



#Added By Sandeep Bara
#Date 16/02/2023

Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {

  /**
   * | Route Outside the Middleware
   | Serial No : 
   */
  Route::controller(ReportController::class)->group(function () {
    Route::post('reports/property/collection', 'collectionReport');
    Route::post('reports/saf/collection', 'safCollection');
    Route::post('reports/property/prop-saf-individual-demand-collection', 'safPropIndividualDemandAndCollection');
  });
});
