<?php

use App\Http\Controllers\CitizenController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\JskController;
use App\Http\Controllers\Payment\BankReconcillationController;
use App\Http\Controllers\Payment\CashVerificationController;
use App\Http\Controllers\Property\ActiveSafController;
use App\Http\Controllers\Property\ActiveSafControllerV2;
use App\Http\Controllers\Property\ApplySafController;
use App\Http\Controllers\Property\ConcessionController;
use App\Http\Controllers\Property\SafCalculatorController;
use App\Http\Controllers\Property\CalculatorController;
use App\Http\Controllers\Property\DocumentOperationController;
use App\Http\Controllers\Property\ObjectionController;
use App\Http\Controllers\Property\PropertyDeactivateController;
use App\Http\Controllers\Property\RainWaterHarvestingController;
use App\Http\Controllers\Property\PropertyBifurcationController;
use App\Http\Controllers\Property\PropMaster;
use App\Http\Controllers\Property\PropertyDetailsController;
use App\Http\Controllers\Property\ClusterController;
use App\Http\Controllers\Property\ConcessionDocController;
use App\Http\Controllers\Property\GbSafController;
use App\Http\Controllers\Property\HoldingTaxController;
use App\Http\Controllers\Property\PropertyController;
use App\Http\Controllers\Property\ReportController;
use App\Http\Controllers\Property\SafDocController;
use App\Http\Controllers\Property\WaiverController;
use App\Http\Controllers\Property\ZoneController;
use App\Http\Controllers\ReferenceController;
use App\Http\Controllers\Property\LocationController;

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

Route::post('api-test', function () {
  return "Welcome to Property Module";
})->middleware('api.key');

// Inside Middleware Routes with API Authenticate 
// Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger', 'expireBearerToken']], function () {
Route::group(['middleware' => ['json.response', 'auth_maker']], function () {

  /**
   * | SAF
     | Serial No : 01
   */
  Route::controller(ApplySafController::class)->group(function () {
    Route::post('saf/apply', 'applySaf');                                                               //Applying Saf Route(2)                                                                      #API_ID=010101
    Route::post('saf/gb-apply', 'applyGbSaf');                                                          // Applying GB Saf (3)                                                                        #API_ID =010102
  });

  Route::controller(ActiveSafController::class)->group(function () {
    Route::get('saf/master-saf', 'masterSaf');                      #_READ->DONE                     // Get all master data in Saf(1)                                                                  #API_ID=010103
    Route::post('saf/edit', 'editSaf');                                                    // Edit Saf By Back Office(24)                                                                                 #API_ID = 010104
    Route::post('saf/inbox', 'inbox');                              #_READ                       // Saf Inbox(3)                                                                                        #API_ID = 010105
    Route::post('saf/btc-inbox', 'btcInbox');                       #_READ                       // Saf Inbox for Back To citizen(23)                                                                    #API_ID = 010106
    Route::post('saf/field-verified-inbox', 'fieldVerifiedInbox');  #_READ                       // Field Verified Inbox (25)                                                                       #API_ID = 010107
    Route::post('saf/outbox', 'outbox');                            #_READ                       // Saf Workflow Outbox and Outbox By search key(4)                                               #API_ID = 010108
    Route::post('saf-details', 'safDetails');                       #_READ                       // Saf Workflow safDetails and safDetails By ID(5)                                               #API_ID = 010109
    Route::post('saf/escalate', 'postEscalate');                                           // Saf Workflow special and safDetails By id(6)                                                           #API_ID = 010110
    Route::post('saf/escalate/inbox/{key?}', 'specialInbox');      #_READ                        // Saf workflow Inbox and Inbox By search key(7)                                                 #API_ID = 010111
    Route::post('saf/independent-comment', 'commentIndependent');                          // Independent Comment for SAF Application(8)                                                            #API_ID = 010112
    Route::post('saf/post/level', 'postNextLevel');                                        // Forward or Backward Application(9)                                                                   #API_ID = 010113
    Route::post('saf/approvalrejection', 'approvalRejectionSaf');                          // Approval Rejection SAF Application(10)                                                             #API_ID = 010114
    Route::post('saf/back-to-citizen', 'backToCitizen');                                   // Saf Application Back To Citizen(11)                                                                       #API_ID = 010115
    Route::post('saf/get-prop-byholding', 'getPropByHoldingNo');     #_READ                      // get Property (search) by ward no and holding no(12)                                          #API_ID = 010116
    Route::post('saf/generate-order-id', 'generateOrderId');                               // Generate Order ID(14)                                                                                     #API_ID = 010117
    Route::get('saf/prop-transactions', 'getPropTransactions');       #_READ                     // Get Property Transactions(17)                                                                #API_ID = 010118
    Route::post('saf/site-verification', 'siteVerification');                              // Ulb TC Site Verification(18)                                                                         #API_ID = 010119
    Route::post('saf/geotagging', 'geoTagging');                                           // Geo Tagging(19)
    Route::post('saf/get-tc-verifications', 'getTcVerifications'); #_READ                        // Get TC Verifications  Data(20)                                                                  #API_ID = 010120
    Route::post('saf/proptransaction-by-id', 'getTransactionBySafPropId');  #_READ               // Get Property Transaction by Property ID or SAF id(22)                                       #API_ID = 010121
    Route::post('saf/get-demand-by-id', 'getDemandBySafId');          #_READ                     // Get the demandable Amount of the Property from Admin Side(26)                                   #API_ID = 010122
    Route::post('saf/verifications-comp', 'getVerifications'); #_READ                       #API_ID = 010123
    Route::post('saf/IndiVerificationsList', 'getSafVerificationList'); #_READ              #API_ID = 010124
    Route::post('saf/static-saf-dtls', 'getStaticSafDetails');          #_READ                   // (27) Static SAf Details                                                                      #API_ID = 010125
    Route::post('saf/offline-saf-payment', 'offlinePaymentSaf');                                  // SAF Payment(15)                                                                             #API_ID = 010126
  });

  /**
   * | SAF Demand and Property contollers
       | Serial No : 02
   */
  Route::controller(SafDocController::class)->group(function () {
    Route::post('saf/document-upload', 'docUpload');                                                    // Upload Documents for SAF (01)                                                          #API_ID = 010201
    Route::post('saf/get-uploaded-documents', 'getUploadDocuments');    #_READ                                // View Uploaded Documents for SAF (02)          #API_ID = 010202
    Route::post('saf/get-doc-list', 'getDocList');                     #_READ                                 // Get Document Lists(03)                       #API_ID = 010203
    Route::post('saf/doc-verify-reject', 'docVerifyReject');                                            // Verify or Reject Saf Documents(04)                                                     #API_ID = 010204
  });

  /**
   * | Property Calculator
       | Serial No : 04 
   */
  Route::controller(SafCalculatorController::class)->group(function () {
    // Route::post('saf-calculation', 'calculateSaf');                                     #API_ID = 010401
  });

  /**
   * | Property Deactivation
   * | Crated By - Sandeep Bara
   * | Created On- 19-11-2022 
       | Serial No : 05
   */
  Route::controller(PropertyDeactivateController::class)->group(function () {
    Route::post('searchByHoldingNo', "readHoldigbyNo");                     #_READ          #API_ID = 010501
    Route::post("get-prop-dtl-for-deactivation", "readPorertyById");        #_READ          #API_ID = 010502
    Route::post('deactivationRequest', "deactivatProperty");                                #API_ID = 010503
    Route::post('inboxDeactivation', "inbox");                               #_READ         #API_ID = 010504
    Route::post('outboxDeactivation', "outbox");                             #_READ         #API_ID = 010505
    Route::post('specialDeactivation', "specialInbox");                      #_READ         #API_ID = 010506
    Route::post('postNextDeactivation', "postNextLevel");                                   #API_ID = 010507
    Route::post('commentIndependentPrpDeactivation', "commentIndependent");                 #API_ID = 010508
    Route::post('postEscalateDeactivation', "postEscalate");                                #API_ID = 010509
    Route::post('getDocumentsPrpDeactivation', "getUplodedDocuments");        #_READ        #API_ID = 010510
    Route::post('approve-reject-deactivation-request', "approvalRejection");                #API_ID = 010511
    Route::post('getDeactivationDtls', "readDeactivationReq");                #_READ        #API_ID = 010512
  });

  /**
   * | PropertyBifurcation Process
   * | Crated By - Sandeep Bara
   * | Created On- 23-11-2022
     | Serial No : 06
   */
  Route::controller(PropertyBifurcationController::class)->group(function () {
    Route::post('searchByHoldingNoBi', "readHoldigbyNo");                                   #API_ID = 010601
    Route::match(["POST", "GET"], 'applyBifurcation/{id}', "addRecord");                    #API_ID = 010602
    Route::post('bifurcationInbox', "inbox");                                               #API_ID = 010603
    Route::post('bifurcationOutbox', "outbox");                                             #API_ID = 010604
    Route::post('bifurcationPostNext', "postNextLevel");                                    #API_ID = 010605
    Route::get('getSafDtls/{id}', "readSafDtls");                                           #API_ID = 010606
    Route::match(["get", "post"], 'documentUpload/{id}', 'documentUpload');                 #API_ID = 010607

    // Route::match(["get", "post"], 'safDocumentUpload/{id}', 'safDocumentUpload');
  });

  /**
   * | Property Concession
       | Serial No : 07
   */
  Route::controller(ConcessionController::class)->group(function () {
    Route::post('concession/apply-concession', 'applyConcession');                 #API_ID = 010701            
    Route::post('concession/inbox', 'inbox');                    
    #_READ   // Concession Inbox                                                        #API_ID = 010702
    Route::post('concession/outbox', 'outbox');                   
    #_READ   // Concession Outbox                                                       #API_ID = 010703
    Route::post('concession/details', 'getDetailsById');         
    #_READ   // Get Concession Details by ID                                            #API_ID = 010704
    Route::post('concession/escalate', 'escalateApplication');                                        
     // escalate application                                                            #API_ID = 010705
    Route::post('concession/special-inbox', 'specialInbox');      
    #_READ    // escalated application inbox                                            #API_ID = 010706
    Route::post('concession/btc-inbox', 'btcInbox');              
    #_READ    // Back To Citizen Inbox                                                  #API_ID = 010707
    Route::post('concession/next-level', 'postNextLevel');                                        
     // Backward Forward Application                                                    #API_ID = 010708
    Route::post('concession/approvalrejection', 'approvalRejection');                                 
     // Approve Reject Application                                                      #API_ID = 010709
    Route::post('concession/backtocitizen', 'backToCitizen');                                       
     // Back To Citizen                                                                 #API_ID = 010710
    Route::post('concession/owner-details', 'getOwnerDetails');    
    #_READ                                                                              #API_ID = 010711
    Route::post('concession/comment-independent', 'commentIndependent');               
    //( Citizen Independent comment and Level Pendings )                                #API_ID = 010712
    Route::post('concession/get-doc-type', 'getDocType');               #_READ          #API_ID = 010713
    Route::post('concession/doc-list', 'concessionDocList');            #_READ          #API_ID = 010714
    Route::post('concession/upload-document', 'uploadDocument');                        #API_ID = 010715
    Route::post('concession/get-uploaded-documents', 'getUploadedDocuments'); #_READ    #API_ID = 010716
    Route::post('concession/doc-verify-reject', 'docVerifyReject');                     #API_ID = 010717
  });


  /**
   * | Property Objection
       | Serial No : 08
   */
  Route::controller(ObjectionController::class)->group(function () {
    Route::post('objection/apply-objection', 'applyObjection');              #API_ID = 010801
    Route::get('objection/objection-type', 'objectionType');                 #API_ID = 010802                  
    Route::post('objection/owner-detailById', 'ownerDetailById');            #API_ID = 010803
    Route::post('objection/forgery-type', 'forgeryType');                    #API_ID = 010804
    Route::post('objection/citizen-forgery-doclist', 'citizenForgeryDocList');#API_ID = 010805

    Route::post('objection/inbox', 'inbox');                                  #API_ID = 010806       
    Route::post('objection/outbox', 'outbox');                                #API_ID = 010807    
    Route::post('objection/details', 'getDetailsById');                       #API_ID = 010808
    Route::post('objection/post-escalate', 'postEscalate');                      
     // Escalate the application and send to special category                 #API_ID = 010809
    Route::post('objection/special-inbox', 'specialInbox');                      
     // Special Inbox                                                         #API_ID = 010810
    Route::post('objection/next-level', 'postNextLevel');                     #API_ID = 010811
    Route::post('objection/approvalrejection', 'approvalRejection');          #API_ID = 010812
    Route::post('objection/backtocitizen', 'backToCitizen');                  #API_ID = 010813
    Route::post('objection/btc-inbox', 'btcInboxList');                       #API_ID = 010814
    Route::post('objection/comment-independent', 'commentIndependent');       #API_ID = 010815
    Route::post('objection/doc-list', 'objectionDocList');                    #API_ID = 010816
    Route::post('objection/upload-document', 'uploadDocument');               #API_ID = 010817
    Route::post('objection/get-uploaded-documents', 'getUploadedDocuments');  #API_ID = 010818
    Route::post('objection/add-members', 'addMembers');                       #API_ID = 010819
    Route::post('objection/citizen-doc-list', 'citizenDocList');              #API_ID = 010820
    Route::post('objection/doc-verify-reject', 'docVerifyReject');            #API_ID = 010821
  });


  /**
   * | Calculator dashboardDate
       | Serial No : 10
   */
  Route::controller(CalculatorController::class)->group(function () {
    Route::post('get-dashboard', 'dashboardDate');                                    #API_ID = 011001
    Route::post('review-calculation', 'reviewCalculation');                           #API_ID = 011002
    // Review for the Calculation
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
    Route::get('get-wardmaster-data', 'getWardMasterData');                             #API_ID = 011101
    Route::post('water-harvesting-application', 'waterHarvestingApplication');          #API_ID = 011102
    Route::post('harvesting/inbox', 'harvestingInbox');                                 #API_ID = 011103
    Route::post('harvesting/outbox', 'harvestingOutbox');                               #API_ID = 011104
    Route::post('harvesting/next-level', 'postNextLevel');                              #API_ID = 011105
    Route::post('harvesting/approval-rejection', 'finalApprovalRejection');             #API_ID = 011106
    Route::post('harvesting/rejection', 'rejectionOfHarvesting');                       #API_ID = 011107
    Route::post('harvesting/details-by-id', 'getDetailsById');                          #API_ID = 011108
    Route::post('harvesting/static-details', 'staticDetails');                          #API_ID = 011109
    Route::post('harvesting/escalate', 'postEscalate');                                 #API_ID = 011110
    Route::post('harvesting/special-inbox', 'specialInbox');                            #API_ID = 011111
    Route::post('harvesting/comment-independent', 'commentIndependent');                #API_ID = 011112
    Route::post('harvesting/get-doc-list', 'getDocList');                               #API_ID = 011113
    Route::post('harvesting/upload-document', 'uploadDocument');                        #API_ID = 011114
    Route::post('harvesting/get-uploaded-documents', 'getUploadedDocuments');           #API_ID = 011115
    Route::post('harvesting/citizen-doc-list', 'citizenDocList');                       #API_ID = 011116
    Route::post('harvesting/doc-verify-reject', 'docVerifyReject');                     #API_ID = 011117
    Route::post('harvesting/field-verification-inbox', 'fieldVerifiedInbox');           #API_ID = 011118
    Route::post('harvesting/backtocitizen', 'backToCitizen');                           #API_ID = 011119
    Route::post('harvesting/btc-inbox', 'btcInboxList');                                #API_ID = 011120
    Route::post('harvesting/site-verification', 'siteVerification');                    #API_ID = 011121
    Route::post('harvesting/get-tc-verifications', 'getTcVerifications');               #API_ID = 011122
  });

  /**
   * | Property Cluster
   * | Created By - Sam kerketta
   * | Created On- 23-11-2022 
       | Serial No : 12
   */
  Route::controller(ClusterController::class)->group(function () {

    #cluster data entry / Master
    Route::post('cluster/get-all-clusters', 'getAllClusters');                            #API_ID = 011201
    Route::post('cluster/edit-cluster-details', 'editClusterDetails');                    #API_ID = 011202
    Route::post('cluster/save-cluster-details', 'saveClusterDetails');                    #API_ID = 011203
    Route::post('cluster/delete-cluster-data', 'deleteClusterData');                      #API_ID = 011204
    Route::post('cluster/get-cluster-by-id', 'getClusterById');                           #API_ID = 011205
    // Remark
    Route::post('cluster/basic-details', 'clusterBasicDtls');                             #API_ID = 011206
    # cluster maping                                                                           
    Route::post('cluster/details-by-holding', 'detailsByHolding');                        #API_ID = 011207
    Route::post('cluster/property-by-cluster', 'propertyByCluster');                      #API_ID = 011208
    Route::post('cluster/save-holding-in-cluster', 'saveHoldingInCluster');               #API_ID = 011209
    Route::post('cluster/get-saf-by-safno', 'getSafBySafNo');                             #API_ID = 011210
    Route::post('cluster/save-saf-in-cluster', 'saveSafInCluster');                       #API_ID = 011211
  });

  /**
   * | Property Document Operation
     | Serial No : 13
   */
  Route::controller(DocumentOperationController::class)->group(function () {
    Route::post('get-all-documents', 'getAllDocuments');                                  #API_ID = 011301
  });

  /**
   * | poperty related type details form ref
       | Serial No : 14 
   */
  Route::controller(PropMaster::class)->group(function () {
    Route::get('prop-usage-type', 'propUsageType');                                       #API_ID = 011401
    Route::get('prop-const-type', 'propConstructionType');                                #API_ID = 011402
    Route::get('prop-occupancy-type', 'propOccupancyType');                               #API_ID = 011403
    Route::get('prop-property-type', 'propPropertyType');                                 #API_ID = 011404
    Route::get('prop-road-type', 'propRoadType');                                         #API_ID = 011405
  });

  /**
   * | Property Details
       | Serial No : 15
   */
  Route::controller(PropertyDetailsController::class)->group(function () {
    Route::post('get-filter-application-details', 'applicationsListByKey');              #API_ID = 011501
    Route::post('get-filter-property-details', 'propertyListByKey');                     #API_ID = 011502
    Route::get('get-list-saf', 'getListOfSaf');                            #API_ID = 011503                   
    Route::post('active-application/get-user-details', 'getUserDetails');            #API_ID = 011504        
  });


  /**
    | Serial No : 17
   */
  Route::controller(ZoneController::class)->group(function () {
    Route::post('get-zone-byUlb', 'getZoneByUlb');                                      #API_ID = 011701

  });

  /**
   * | Calculation of Yearly Property Tax and generation of its demand
    | Serial No-16 
   */
  Route::controller(HoldingTaxController::class)->group(function () {
    Route::post('v1/generate-holding-demand', 'generateHoldingDemand');                       
    //Property/Holding Yearly Holding Tax Generation                              #API_ID = 011601
    Route::post('get-holding-dues', 'getHoldingDues');                                       
     //Property/ Holding Dues                                           #API_ID = 011602                      
    Route::post('generate-prop-orderid', 'generateOrderId');                     
     //Generate Property Order ID                                                   #API_ID = 011603
    Route::post('offline-payment-holding', 'offlinePaymentHolding');              
    //Payment Holding                                                               #API_ID = 011604
    Route::post('prop/get-cluster-holding-due', 'getClusterHoldingDues');         
    //Property Cluster Dues                                                         #API_ID = 011605
    Route::post('prop/cluster-payment', 'clusterPayment');                       
     //Cluster Payment                                                              #API_ID = 011606
    Route::post('prop-dues', 'propertyDues');                                     
    //Property Dues Dynamic                                                        #API_ID = 011607            
    Route::post('legacy-payment-holding', 'legacyPaymentHolding');               
    //Legacy Property Payment                                                      #API_ID = 011608
  });

  /**
    | Serial No : 18
   */
  Route::controller(ActiveSafControllerV2::class)->group(function () {
    Route::post('saf/delete-citizen-saf', 'deleteCitizenSaf');                      #API_ID = 011801      
    Route::post('saf/edit-citizen-saf', 'editCitizenSaf');                      #API_ID = 011802               
    Route::post('saf/memo-receipt', 'memoReceipt');                                 #API_ID = 011803
    Route::post('saf/verify-holding', 'verifyHoldingNo');                           #API_ID = 011804
    Route::post('saf/list-apartment', 'getAptList');                  #API_ID = 011805                        
    Route::post('saf/pending-geotagging-list', 'pendingGeoTaggingList');           #API_ID = 011806   
    Route::post('saf/get-cluster-saf-due', 'getClusterSafDues');                  #API_ID = 011807     
    Route::post('saf/cluster-saf-payment', 'clusterSafPayment');                  #API_ID = 011808       
    Route::post('saf/edit-active-saf', 'editActiveSaf');                        #API_ID = 011809              
  });

  /**
    | Serial No : 19
   */
  Route::controller(PropertyController::class)->group(function () {
    Route::post('caretaker-otp', 'caretakerOtp');                            #API_ID = 011901
    Route::post('caretaker-property-tagging', 'caretakerPropertyTag');       #API_ID = 011902
    Route::post('citizen-holding-saf', 'citizenHoldingSaf');                 #API_ID = 011903
    Route::post('basic-edit', 'basicPropertyEdit');                          #API_ID = 011904
    Route::post('check-property', 'CheckProperty');                          #API_ID = 011905
    Route::post('citizen-status-update', 'citizenStatusUpdate');             #API_ID = 011906

  });

  /**
    | Serial No : 20
   */
  Route::controller(GbSafController::class)->group(function () {
    Route::post('gbsaf/inbox', 'inbox');                             // 01            #API_ID = 010103
    Route::post('gbsaf/outbox', 'outbox');                                            #API_ID = 010104
    Route::post('gbsaf/next-level', 'postNextLevel');
    Route::post('gbsaf/final-approve-reject', 'approvalRejectionGbSaf');
    Route::post('gbsaf/inbox-field-verification', 'fieldVerifiedInbox');              #API_ID = 4181
    Route::post('gbsaf/site-verification', 'siteVerification');
    Route::post('gbsaf/geo-tagging', 'geoTagging');                                   #API_ID = 010119
    Route::post('gbsaf/tc-verification', 'getTcVerifications');
    Route::post('gbsaf/back-to-citizen', 'backToCitizen');                            #API_ID = 010111
    Route::post('gbsaf/btc-inbox', 'btcInbox');                                       #API_ID = 4179
    Route::post('gbsaf/post-escalate', 'postEscalate');                               #API_ID = 010106
    Route::post('gbsaf/special-inbox', 'specialInbox');                               #API_ID = 010107
    Route::post('gbsaf/static-details', 'getStaticSafDetails');                       #API_ID = 010127
    Route::post('gbsaf/get-uploaded-document', 'getUploadedDocuments');               #API_ID = 010102
    Route::post('gbsaf/upload-documents', 'uploadDocument');                          #API_ID = 010201
    Route::post('gbsaf/get-doc-list', 'getDocList');                                  #API_ID = 010203
    Route::post('gbsaf/doc-verify-reject', 'docVerifyReject');                        #API_ID = 010204
    Route::post('gbsaf/independent-comment', 'commentIndependent');                   #API_ID = 010108
    Route::post('gbsaf/details', 'gbSafDetails');                                     #API_ID = 010104
  });

  /**
   * | 
   */
  Route::controller(JskController::class)->group(function () {
    Route::post('dashboard-details', 'propDashboardDtl');               // 01
    Route::post('dashboard', 'propDashboard');
  });


  Route::controller(WaiverController::class)->group(function () {
    Route::post('waiver/apply', 'apply');                                                 #API_ID = ""
    Route::post('waiver/final-approval', 'approvalRejection');
    Route::post('waiver/approved-list', 'approvedApplication');                           #API_ID = ""
    Route::post('waiver/application-detail', 'applicationDetails');
    Route::post('waiver/list-inbox', 'inbox');                                            #API_ID = 010103
    Route::post('waiver/uploaded-documents', 'getUploadedDocuments');                     #API_ID = 010202
    Route::post('waiver/verify-document', 'docVerifyReject');                             #API_ID = 010204
    Route::post('waiver/static-details', 'staticDetails');
    Route::post('waiver/final-waived', 'finalWaivedAmount');
  });
});


/**
 * | Not Authenticated Apis
 */

/**
 * | SAF
     | Serial No : 01
 */
Route::group(['middleware' => ['json.response', 'auth_maker']], function () {
  Route::controller(ActiveSafController::class)->group(function () {
    Route::post('saf/master-saf', 'masterSaf');                                                         // Get all master data in Saf(1)
    Route::post('saf/saf-payment', 'paymentSaf');                                                       // SAF Payment(15)
    Route::post('saf/calculate-by-saf-id', 'calculateSafBySafId');                                      // Calculate SAF By SAF ID From Citizen(13)
    Route::post('saf/independent/generate-order-id', 'generateOrderId');                                // Generate Order ID(14)
    Route::post('saf/payment-receipt', 'generatePaymentReceipt');                                       // Generate payment Receipt(16)                                                               #API_ID = 010116
  });

  /**
   * | Route Outside the Middleware
   | Serial No : 
   */
  Route::controller(CalculatorController::class)->group(function () {
    Route::post('calculatePropertyTax', 'calculator');
  });

  /**
   * | Route Outside the Authenticated Middleware 
    Serial No : 18
   */
  Route::controller(ActiveSafControllerV2::class)->group(function () {
    Route::post('search-holding', 'searchHolding');                     //04                #API_ID = 4180
  });
  /**
   * | Holding Tax Controller(Created By-Anshu Kumar)
   | Serial No-16
   */
  Route::controller(HoldingTaxController::class)->group(function () {
    Route::post('payment-holding', 'paymentHolding');                                         // (04) Payment Holding (For Testing Purpose)
    Route::post('prop-payment-receipt', 'propPaymentReceipt');                                // (05) Generate Property Payment Receipt                                                               #API_ID = 011605
    Route::post('independent/get-holding-dues', 'getHoldingDues');                            // (07) Property/ Holding Dues
    Route::post('independent/generate-prop-orderid', 'generateOrderId');                      // (08) Generate Property Order ID
    Route::post('prop-payment-history', 'propPaymentHistory');                                // (06) Property Payment History                                                                           #API_ID = 011606
    Route::post('prop-ulb-receipt', 'proUlbReceipt');                                         // (09) Property Ulb Payment Receipt
    Route::post('prop-comparative-demand', 'comparativeDemand');                              // (10) Property Comparative Demand                                                                        #API_ID = 011610
    Route::post('cluster/payment-history', 'clusterPaymentHistory');                           // (13) Cluster Payment History                                                                           #API_ID = 011613
    Route::post('cluster/payment-receipt', 'clusterPaymentReceipt');                           // (14) Generate Cluster Payment Receipt for Saf and Property                                      #API_ID = 011613
  });
});
/**
 * | Get Reference List and Ulb Master Crud Operation
 * | Created By : Tannu Verma
 * | Created At : 20-05-2023
   | Serial No. : 21
 * | Status: CLosed
 */
Route::controller(ReferenceController::class)->group(function () {

  Route::post('v1/building-rental-const', 'listBuildingRentalconst');                              //01
  Route::post('v1/get-forgery-type', 'listpropForgeryType');                                       //02
  Route::post('v1/get-rental-value', 'listPropRentalValue');                                       //03
  Route::post('v1/building-rental-rate', 'listPropBuildingRentalrate');                            //04
  Route::post('v1/vacant-rental-rate', 'listPropVacantRentalrate');                                //05
  Route::post('v1/get-construction-list', 'listPropConstructiontype');                             //06
  Route::post('v1/floor-type', 'listPropFloor');                                                   //07
  Route::post('v1/gb-building-usage-type', 'listPropgbBuildingUsagetype');                         //08
  Route::post('v1/gb-prop-usage-type', 'listPropgbPropUsagetype');                                 //09
  Route::post('v1/prop-objection-type', 'listPropObjectiontype');                                  //10
  Route::post('v1/prop-occupancy-factor', 'listPropOccupancyFactor');                              //11
  Route::post('v1/prop-occupancy-type', 'listPropOccupancytype');                                  //12
  Route::post('v1/prop-ownership-type', 'listPropOwnershiptype');                                  //13
  Route::post('v1/prop-penalty-type', 'listPropPenaltytype');                                      //14
  Route::post('v1/prop-rebate-type', 'listPropRebatetype');                                        //15
  Route::post('v1/prop-road-type', 'listPropRoadtype');                                            //16
  Route::post('v1/prop-transfer-mode', 'listPropTransfermode');                                    //17
  Route::post('v1/get-prop-type', 'listProptype');                                                 //18
  Route::post('v1/prop-usage-type', 'listPropUsagetype');                                          //19
});

/**
 * | Created On-31-01-2023 
 * | Created by-Mrinal Kumar
 * | Payment Cash Verification
 */
Route::controller(CashVerificationController::class)->group(function () {
  Route::post('list-cash-verification', 'cashVerificationList');              //01
  Route::post('verified-cash-verification', 'verifiedCashVerificationList');  //02
  Route::post('tc-collections', 'tcCollectionDtl');                           //03
  Route::post('verified-tc-collections', 'verifiedTcCollectionDtl');          //04
  Route::post('verify-cash', 'cashVerify');                                   //05
  Route::post('cash-receipt', 'cashReceipt');                                 //06
  Route::post('edit-chequedtl', 'editChequeNo');                              //07
});

Route::controller(BankReconcillationController::class)->group(function () {
  Route::post('search-transaction', 'searchTransaction');
  Route::post('cheque-dtl-by-id', 'chequeDtlById');
  Route::post('cheque-clearance', 'chequeClearance');
});





#Added By Sandeep Bara
#Date 16/02/2023

// Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger', 'expireBearerToken']], function () {

/**
 * | Route Outside the Middleware
   | Serial No : 
 */
Route::controller(ReportController::class)->group(function () {
  Route::post('reports/property/collection', 'collectionReport');                        #API_ID = pr1.1
  Route::post('reports/saf/collection', 'safCollection');                                #API_ID = pr2.1
  Route::post('reports/property/prop-saf-individual-demand-collection', 'safPropIndividualDemandAndCollection');                                               #API_ID = pr3.1
  Route::post('reports/saf/levelwisependingform', 'levelwisependingform');               #API_ID = pr4.1
  Route::post('reports/saf/levelformdetail', 'levelformdetail');                         #API_ID = pr4.2
  Route::post('reports/saf/leveluserpending', 'levelUserPending');                       #API_ID = pr4.2.1
  Route::post('reports/saf/userwiselevelpending', 'userWiseLevelPending');               #API_ID = 
  Route::post('reports/saf/userWiseWardWireLevelPending', 'userWiseWardWireLevelPending');#API_ID = pr4.2.1.1
  Route::post('reports/saf/saf-sam-fam-geotagging', 'safSamFamGeotagging');             #API_ID = pr5.1        
  Route::post('reports/ward-wise-holding', 'wardWiseHoldingReport');                    #API_ID = pr6.1 
  Route::post('reports/list-fy', 'listFY');                          
  Route::post('reports/print-bulk-receipt', 'bulkReceipt');        
  Route::post('reports/property/gbsaf-collection', 'gbSafCollection');    
  Route::post('reports/property/individual-demand-collection', 'propIndividualDemandCollection'); 
                                                                                        #API_ID = pr13.1 
  Route::post('reports/property/gbsaf-individual-demand-collection', 'gbsafIndividualDemandCollection');
                                                                                        #API_ID = pr14.1 
  Route::post('reports/not-paid-from-2016', 'notPaidFrom2016');       
  Route::post('reports/previous-year-paid-not-current-year', 'previousYearPaidButnotCurrentYear'); 
  Route::post('reports/dcb-piechart', 'dcbPieChart');                                             
  Route::post('reports/prop/saf/collection', 'propSafCollection');                          
  Route::post('reports/rebate/penalty', 'rebateNpenalty');

  Route::post('reports/property/payment-mode-wise-summery', 'PropPaymentModeWiseSummery'); 
  Route::post('reports/payment-mode-wise-summery', 'PaymentModeWiseSummery');         #API_ID = pr1.2
  Route::post('reports/saf/payment-mode-wise-summery', 'SafPaymentModeWiseSummery');  #API_ID = pr2.2
  Route::post('reports/property/dcb', 'PropDCB');                              #API_ID = pr7.1                 
  Route::post('reports/property/ward-wise-dcb', 'PropWardWiseDCB');                       
  Route::post('reports/property/holding-wise-fine-rebate', 'PropFineRebate');        #API_ID = pr9.1           
  Route::post('reports/property/deactivated-list', 'PropDeactedList');        #API_ID = pr10.1                 
  Route::post('reports/mpl', 'mplReport');                    

});
// });

/**
    | Test Purpose
    | map locating 
 */
Route::controller(PropertyController::class)->group(function () {
  Route::post('getpropLatLong', 'getpropLatLong');                             // 01
});

#Added By Prity Pandey
#Date 31/10/2023
#Route for getting location based ward list 
Route::controller(LocationController::class)->group(function () {
  Route::post('location', 'location_list');

  Route::post('location/ward-list', 'bindLocationWithWards');
});

#Route for getting citizen details based on citizen Id 
Route::controller(CitizenController::class)->group(function () {
  Route::post('citizen/details', 'citizenDetailsWithCitizenId')->middleware(['json.response', 'auth_maker']);
                                                                                              #API_ID = 4180
});
