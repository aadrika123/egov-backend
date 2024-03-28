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
use App\Http\Controllers\Property\MasterReferenceController;
use App\Http\Controllers\ReferenceController;
use App\Http\Controllers\Property\LocationController;
use App\Http\Controllers\Property\PropertyMutationController;

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
    Route::post('saf/apply', 'applySaf');                                 // Applying Saf Route                                                   #API_ID = 010101
    Route::post('saf/gb-apply', 'applyGbSaf');                            // Applying GB Saf                                                     #API_ID = 010102
  });



  Route::controller(ActiveSafController::class)->group(function () {
    Route::get('saf/master-saf', 'masterSaf');                               // Get all master data in Saf                                       #API_ID = 010103
    Route::post('saf/edit', 'editSaf');                                      // Edit Saf By Back Office                                          #API_ID = 010104                                                 
    Route::post('saf/inbox', 'inbox');                                       // Saf Inbox                                                        #API_ID = 010105
    Route::post('saf/btc-inbox', 'btcInbox');                                // Saf Inbox for Back To citizen                                    #API_ID = 010106                    
    Route::post('saf/field-verified-inbox', 'fieldVerifiedInbox');           // Field Verified Inbox                                             #API_ID = 010107                          
    Route::post('saf/outbox', 'outbox');                                     // Saf Workflow Outbox and Outbox By search key                     #API_ID = 010108
    Route::post('saf-details', 'safDetails');                                // Saf Workflow safDetails and safDetails By ID                     #API_ID = 010109
    Route::post('saf/escalate', 'postEscalate');                             // Saf Workflow special and safDetails By id                        #API_ID = 010110
    Route::post('saf/escalate/inbox/{key?}', 'specialInbox');                // Saf workflow Inbox and Inbox By search key                       #API_ID = 010111
    Route::post('saf/independent-comment', 'commentIndependent');            // Independent Comment for SAF Application                          #API_ID = 010112
    Route::post('saf/post/level', 'postNextLevel');                          // Forward or Backward Application                                  #API_ID = 010113
    Route::post('saf/approvalrejection', 'approvalRejectionSaf');            // Approval Rejection SAF Application                               #API_ID = 010114
    Route::post('saf/back-to-citizen', 'backToCitizen');                     // Saf Application Back To Citizen                                  #API_ID = 010115
    Route::post('saf/get-prop-byholding', 'getPropByHoldingNo');             // get Property (search) by ward no and holding no                  #API_ID = 010116
    Route::post('saf/generate-order-id', 'generateOrderId');                 // Generate Order ID                                                #API_ID = 010117
    Route::get('saf/prop-transactions', 'getPropTransactions');              // Get Property Transactions                                        #API_ID = 010118
    Route::post('saf/site-verification', 'siteVerification');                // Ulb TC Site Verification                                         #API_ID = 010119
    Route::post('saf/geotagging', 'geoTagging');                             // Geo Tagging                                                      #API_ID = 010120
    Route::post('saf/get-tc-verifications', 'getTcVerifications');           // Get TC Verifications Data                                        #API_ID = 010121
    Route::post('saf/proptransaction-by-id', 'getTransactionBySafPropId');   // Get Property Transaction by Property ID or SAF id                #API_ID = 010122
    Route::post('saf/get-demand-by-id', 'getDemandBySafId');                 // Get the demandable Amount of the Property from Admin Side        #API_ID = 010123
    Route::post('saf/verifications-comp', 'getVerifications');                                                                                   #API_ID = 010124
    Route::post('saf/IndiVerificationsList', 'getSafVerificationList');                                                                          #API_ID = 010125
    Route::post('saf/static-saf-dtls', 'getStaticSafDetails');               //Static SAf Details                                                #API_ID = 010126
    Route::post('saf/offline-saf-payment', 'offlinePaymentSaf');             // SAF Payment                                                      #API_ID = 010127
  });

  /**
   * | SAF Demand and Property contollers
       | Serial No : 02
   */
  Route::controller(SafDocController::class)->group(function () {
    Route::post('saf/document-upload', 'docUpload');                    // Upload Documents for SAF                 #API_ID = 010201
    Route::post('saf/get-uploaded-documents', 'getUploadDocuments');    // View Uploaded Documents for SAF          #API_ID = 010202
    Route::post('saf/get-doc-list', 'getDocList');                      // Get Document Lists                       #API_ID = 010203
    Route::post('saf/doc-verify-reject', 'docVerifyReject');            // Verify or Reject Saf Documents           #API_ID = 010204
  });

  /**
   * | Property Deactivation
   * | Crated By - Sandeep Bara
   * | Created On- 19-11-2022 
       | Serial No : 04
   */
  Route::controller(PropertyDeactivateController::class)->group(function () {
    Route::post('searchByHoldingNo', "readHoldigbyNo");                        #API_ID = 010401
    Route::post("get-prop-dtl-for-deactivation", "readPorertyById");           #API_ID = 010402
    Route::post('deactivationRequest', "deactivatProperty");                   #API_ID = 010403
    Route::post('inboxDeactivation', "inbox");                                 #API_ID = 010404
    Route::post('outboxDeactivation', "outbox");                               #API_ID = 010405
    Route::post('specialDeactivation', "specialInbox");                        #API_ID = 010406
    Route::post('postNextDeactivation', "postNextLevel");                      #API_ID = 010407
    Route::post('commentIndependentPrpDeactivation', "commentIndependent");    #API_ID = 010408
    Route::post('postEscalateDeactivation', "postEscalate");                   #API_ID = 010409
    Route::post('getDocumentsPrpDeactivation', "getUplodedDocuments");         #API_ID = 010410
    Route::post('approve-reject-deactivation-request', "approvalRejection");   #API_ID = 010411
    Route::post('getDeactivationDtls', "readDeactivationReq");                 #API_ID = 010412
  });


  /**
   * | Property Concession
       | Serial No : 06
   */
  Route::controller(ConcessionController::class)->group(function () {
    Route::post('concession/apply-concession', 'applyConcession');                                                                 #API_ID = 010601            
    Route::post('concession/inbox', 'inbox');                            // Concession Inbox                                       #API_ID = 010602
    Route::post('concession/outbox', 'outbox');                          // Concession Outbox                                      #API_ID = 010603
    Route::post('concession/details', 'getDetailsById');                 // Get Concession Details by ID                           #API_ID = 010604
    Route::post('concession/escalate', 'escalateApplication');           // escalate application                                   #API_ID = 010605
    Route::post('concession/special-inbox', 'specialInbox');             // escalated application inbox                            #API_ID = 010606
    Route::post('concession/btc-inbox', 'btcInbox');                     // Back To Citizen Inbox                                  #API_ID = 010607
    Route::post('concession/next-level', 'postNextLevel');               // Backward Forward Application                           #API_ID = 010608
    Route::post('concession/approvalrejection', 'approvalRejection');    // Approve Reject Application                             #API_ID = 010609
    Route::post('concession/backtocitizen', 'backToCitizen');            // Back To Citizen                                        #API_ID = 010610
    Route::post('concession/owner-details', 'getOwnerDetails');                                                                    #API_ID = 010611
    Route::post('concession/comment-independent', 'commentIndependent'); //( Citizen Independent comment and Level Pendings )      #API_ID = 010612
    Route::post('concession/get-doc-type', 'getDocType');                                                                          #API_ID = 010613
    Route::post('concession/doc-list', 'concessionDocList');                                                                       #API_ID = 010614
    Route::post('concession/upload-document', 'uploadDocument');                                                                   #API_ID = 010615
    Route::post('concession/get-uploaded-documents', 'getUploadedDocuments');                                                      #API_ID = 010616
    Route::post('concession/doc-verify-reject', 'docVerifyReject');                                                                #API_ID = 010617
  });


  /**
   * | Property Objection
       | Serial No : 07
   */
  Route::controller(ObjectionController::class)->group(function () {
    Route::post('objection/apply-objection', 'applyObjection');                                                                        #API_ID = 010701
    Route::get('objection/objection-type', 'objectionType');                                                                           #API_ID = 010702                  
    Route::post('objection/owner-detailById', 'ownerDetailById');                                                                      #API_ID = 010703
    Route::post('objection/forgery-type', 'forgeryType');                                                                              #API_ID = 010704
    Route::post('objection/citizen-forgery-doclist', 'citizenForgeryDocList');                                                         #API_ID = 010705
    Route::post('objection/inbox', 'inbox');                                                                                           #API_ID = 010706       
    Route::post('objection/outbox', 'outbox');                                                                                         #API_ID = 010707    
    Route::post('objection/details', 'getDetailsById');                                                                                #API_ID = 010708
    Route::post('objection/post-escalate', 'postEscalate');    // Escalate the application and send to special category                #API_ID = 010709
    Route::post('objection/special-inbox', 'specialInbox');    // Special Inbox                                                        #API_ID = 010710
    Route::post('objection/next-level', 'postNextLevel');                                                                              #API_ID = 010711
    Route::post('objection/approvalrejection', 'approvalRejection');                                                                   #API_ID = 010712
    Route::post('objection/backtocitizen', 'backToCitizen');                                                                           #API_ID = 010713
    Route::post('objection/btc-inbox', 'btcInboxList');                                                                                #API_ID = 010714
    Route::post('objection/comment-independent', 'commentIndependent');                                                                #API_ID = 010715
    Route::post('objection/doc-list', 'objectionDocList');                                                                             #API_ID = 010716
    Route::post('objection/upload-document', 'uploadDocument');                                                                        #API_ID = 010717
    Route::post('objection/get-uploaded-documents', 'getUploadedDocuments');                                                           #API_ID = 010718
    Route::post('objection/add-members', 'addMembers');                                                                                #API_ID = 010719
    Route::post('objection/citizen-doc-list', 'citizenDocList');                                                                       #API_ID = 010720
    Route::post('objection/doc-verify-reject', 'docVerifyReject');                                                                     #API_ID = 010721
  });


  /**
   * | Calculator dashboardDate
       | Serial No : 8
   */
  Route::controller(CalculatorController::class)->group(function () {
    Route::post('get-dashboard', 'dashboardDate');                               #API_ID = 010801
    Route::post('review-calculation', 'reviewCalculation');                      #API_ID = 010802
    // Review for the Calculation
  });


  /**
   * | Rain water Harvesting
   * | Created By - Sam kerketta
   * | Created On- 22-11-2022
   * | Modified By - Mrinal Kumar
   * | Modification On- 10-12-2022
   * 
       | Serial No : 9
   */
  Route::controller(RainWaterHarvestingController::class)->group(function () {
    Route::get('get-wardmaster-data', 'getWardMasterData');                      #API_ID = 010901
    Route::post('water-harvesting-application', 'waterHarvestingApplication');   #API_ID = 010902
    Route::post('harvesting/inbox', 'harvestingInbox');                          #API_ID = 010903
    Route::post('harvesting/outbox', 'harvestingOutbox');                        #API_ID = 010904
    Route::post('harvesting/next-level', 'postNextLevel');                       #API_ID = 010905
    Route::post('harvesting/approval-rejection', 'finalApprovalRejection');      #API_ID = 010906
    Route::post('harvesting/rejection', 'rejectionOfHarvesting');                #API_ID = 010907
    Route::post('harvesting/details-by-id', 'getDetailsById');                   #API_ID = 010908
    Route::post('harvesting/static-details', 'staticDetails');                   #API_ID = 010909
    Route::post('harvesting/escalate', 'postEscalate');                          #API_ID = 010910
    Route::post('harvesting/special-inbox', 'specialInbox');                     #API_ID = 010911
    Route::post('harvesting/comment-independent', 'commentIndependent');         #API_ID = 010912
    Route::post('harvesting/get-doc-list', 'getDocList');                        #API_ID = 010913
    Route::post('harvesting/upload-document', 'uploadDocument');                 #API_ID = 010914
    Route::post('harvesting/get-uploaded-documents', 'getUploadedDocuments');    #API_ID = 010915
    Route::post('harvesting/citizen-doc-list', 'citizenDocList');                #API_ID = 010916
    Route::post('harvesting/doc-verify-reject', 'docVerifyReject');              #API_ID = 010917
    Route::post('harvesting/field-verification-inbox', 'fieldVerifiedInbox');    #API_ID = 010918
    Route::post('harvesting/backtocitizen', 'backToCitizen');                    #API_ID = 010919
    Route::post('harvesting/btc-inbox', 'btcInboxList');                         #API_ID = 010920
    Route::post('harvesting/site-verification', 'siteVerification');             #API_ID = 010921
    Route::post('harvesting/get-tc-verifications', 'getTcVerifications');        #API_ID = 010922
  });

  /**
   * | Property Cluster
   * | Created By - Sam kerketta
   * | Created On- 23-11-2022 
       | Serial No : 10
   */
  Route::controller(ClusterController::class)->group(function () {

    #cluster data entry / Master
    Route::post('cluster/get-all-clusters', 'getAllClusters');                  #API_ID = 011001
    Route::post('cluster/edit-cluster-details', 'editClusterDetails');          #API_ID = 011002
    Route::post('cluster/save-cluster-details', 'saveClusterDetails');          #API_ID = 011003
    Route::post('cluster/delete-cluster-data', 'deleteClusterData');            #API_ID = 011004
    Route::post('cluster/get-cluster-by-id', 'getClusterById');                 #API_ID = 011005
    // Remark
    Route::post('cluster/basic-details', 'clusterBasicDtls');                   #API_ID = 011006
    # cluster maping                                                                           
    Route::post('cluster/details-by-holding', 'detailsByHolding');              #API_ID = 011007
    Route::post('cluster/save-holding-in-cluster', 'saveHoldingInCluster');     #API_ID = 011009
    Route::post('cluster/get-saf-by-safno', 'getSafBySafNo');                   #API_ID = 011010
    Route::post('cluster/save-saf-in-cluster', 'saveSafInCluster');             #API_ID = 011011
  });



  /**
   * | poperty related type details form ref
       | Serial No : 12 
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
       | Serial No : 13
   */
  Route::controller(PropertyDetailsController::class)->group(function () {
    Route::post('get-filter-application-details', 'applicationsListByKey');       #API_ID = 011301
    Route::post('get-filter-property-details', 'propertyListByKey');              #API_ID = 011302
    Route::get('get-list-saf', 'getListOfSaf');                                   #API_ID = 011303                   
    Route::post('active-application/get-user-details', 'getUserDetails');         #API_ID = 011304        
  });


  /**
    | Serial No : 14
   */
  Route::controller(ZoneController::class)->group(function () {
    Route::post('get-zone-byUlb', 'getZoneByUlb');                              #API_ID = 011401

  });

  /**
   * | Calculation of Yearly Property Tax and generation of its demand
    | Serial No-15 
   */
  Route::controller(HoldingTaxController::class)->group(function () {
    Route::post('v1/generate-holding-demand', 'generateHoldingDemand');      //Property/Holding Yearly Holding Tax Generation             #API_ID = 011501
    Route::post('get-holding-dues', 'getHoldingDues');                       //Property/ Holding Dues                                     #API_ID = 011502                      
    Route::post('generate-prop-orderid', 'generateOrderId');                 //Generate Property Order ID                                 #API_ID = 011503
    Route::post('offline-payment-holding', 'offlinePaymentHolding');         //Payment Holding                                            #API_ID = 011504
    Route::post('prop/get-cluster-holding-due', 'getClusterHoldingDues');    //Property Cluster Dues                                      #API_ID = 011505
    Route::post('prop/cluster-payment', 'clusterPayment');                   //Cluster Payment                                            #API_ID = 011506
    Route::post('prop-dues', 'propertyDues');                                //Property Dues Dynamic                                      #API_ID = 011507            
    Route::post('legacy-payment-holding', 'legacyPaymentHolding');           //Legacy Property Payment                                    #API_ID = 011508
  });

  /**
    | Serial No : 16
   */
  Route::controller(ActiveSafControllerV2::class)->group(function () {
    Route::post('saf/delete-citizen-saf', 'deleteCitizenSaf');              #API_ID = 011601      
    Route::post('saf/edit-citizen-saf', 'editCitizenSaf');                  #API_ID = 011602               
    Route::post('saf/memo-receipt', 'memoReceipt');                         #API_ID = 011603
    Route::post('saf/verify-holding', 'verifyHoldingNo');                   #API_ID = 011604
    Route::post('saf/list-apartment', 'getAptList');                        #API_ID = 011605                        
    Route::post('saf/pending-geotagging-list', 'pendingGeoTaggingList');    #API_ID = 011606   
    Route::post('saf/get-cluster-saf-due', 'getClusterSafDues');            #API_ID = 011607     
    Route::post('saf/cluster-saf-payment', 'clusterSafPayment');            #API_ID = 011608       
    // Route::post('saf/edit-active-saf', 'editActiveSaf');                    #API_ID = 011609              
  });

  /**
    | Serial No : 17
   */
  Route::controller(PropertyController::class)->group(function () {
    Route::post('caretaker-otp', 'caretakerOtp');                            #API_ID = 011701
    Route::post('caretaker-property-tagging', 'caretakerPropertyTag');       #API_ID = 011702
    Route::post('citizen-holding-saf', 'citizenHoldingSaf');                 #API_ID = 011703
    Route::post('basic-edit', 'basicPropertyEdit');                          #API_ID = 011704
    Route::post('check-property', 'CheckProperty');                          #API_ID = 011705
    Route::post('citizen-status-update', 'citizenStatusUpdate');             #API_ID = 011706

  });

  /**
    | Serial No : 18
   */
  Route::controller(GbSafController::class)->group(function () {
    Route::post('gbsaf/inbox', 'inbox');                                       #API_ID = 011801
    Route::post('gbsaf/outbox', 'outbox');                                     #API_ID = 011802
    Route::post('gbsaf/next-level', 'postNextLevel');                          #API_ID = 011803
    Route::post('gbsaf/final-approve-reject', 'approvalRejectionGbSaf');       #API_ID = 011804
    Route::post('gbsaf/inbox-field-verification', 'fieldVerifiedInbox');       #API_ID = 011805
    Route::post('gbsaf/site-verification', 'siteVerification');                #API_ID = 011806
    Route::post('gbsaf/geo-tagging', 'geoTagging');                            #API_ID = 011807
    Route::post('gbsaf/tc-verification', 'getTcVerifications');                #API_ID = 011808
    Route::post('gbsaf/back-to-citizen', 'backToCitizen');                     #API_ID = 011809
    Route::post('gbsaf/btc-inbox', 'btcInbox');                                #API_ID = 011810
    Route::post('gbsaf/post-escalate', 'postEscalate');                        #API_ID = 011811
    Route::post('gbsaf/special-inbox', 'specialInbox');                        #API_ID = 011812
    Route::post('gbsaf/static-details', 'getStaticSafDetails');                #API_ID = 011813
    Route::post('gbsaf/get-uploaded-document', 'getUploadedDocuments');        #API_ID = 011814
    Route::post('gbsaf/upload-documents', 'uploadDocument');                   #API_ID = 011815
    Route::post('gbsaf/get-doc-list', 'getDocList');                           #API_ID = 011816
    Route::post('gbsaf/doc-verify-reject', 'docVerifyReject');                 #API_ID = 011817
    Route::post('gbsaf/independent-comment', 'commentIndependent');            #API_ID = 011818
    Route::post('gbsaf/details', 'gbSafDetails');                              #API_ID = 011819
  });

  /**
  |  Serial No : 19
   */
  Route::controller(JskController::class)->group(function () {
    Route::post('dashboard-details', 'propDashboardDtl');               #API_ID = 011901
    Route::post('dashboard', 'propDashboard');                          #API_ID = 011902
  });

  /**
  |  Serial No : 20
   */
  Route::controller(WaiverController::class)->group(function () {
    Route::post('waiver/apply', 'apply');                                      #API_ID = 012001
    Route::post('waiver/final-approval', 'approvalRejection');                 #API_ID = 012002                  
    Route::post('waiver/approved-list', 'approvedApplication');                #API_ID = 012003
    Route::post('waiver/application-detail', 'applicationDetails');            #API_ID = 012004
    Route::post('waiver/list-inbox', 'inbox');                                 #API_ID = 012005
    Route::post('waiver/uploaded-documents', 'getUploadedDocuments');          #API_ID = 012006
    Route::post('waiver/verify-document', 'docVerifyReject');                  #API_ID = 012007
    Route::post('waiver/static-details', 'staticDetails');                     #API_ID = 012008
    Route::post('waiver/final-waived', 'finalWaivedAmount');                   #API_ID = 012009
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
    Route::post('saf/master-saf', 'masterSaf');                                 // Get all master data in Saf                                  #API_ID = 010103
    Route::post('saf/saf-payment',  'paymentSaf');                              // SAF Payment                                                 #API_ID = 010128
    Route::post('saf/calculate-by-saf-id', 'calculateSafBySafId');              // Calculate SAF By SAF ID From Citizen                        #API_ID = 010129
    Route::post('saf/independent/generate-order-id', 'generateOrderId');        // Generate Order ID                                           #API_ID = 010117
    Route::post('saf/payment-receipt', 'generatePaymentReceipt');               // Generate payment Receipt                                    #API_ID = 010130
  });

  /**
   * | Route Outside the Middleware
   | Serial No : 8
   */
  Route::controller(CalculatorController::class)->group(function () {
    Route::post('calculatePropertyTax', 'calculator');
  });

  /**
   * | Route Outside the Authenticated Middleware 
    Serial No : 16
   */
  Route::controller(ActiveSafControllerV2::class)->group(function () {
    Route::post('search-holding', 'searchHolding');                   #API_ID = 011610
  });
  /**
   * | Holding Tax Controller(Created By-Anshu Kumar)
   | Serial No-15
   */
  Route::controller(HoldingTaxController::class)->group(function () {
    Route::post('payment-holding', 'paymentHolding');                       //Payment Holding (For Testing Purpose)                            #API_ID = 011509
    Route::post('prop-payment-receipt', 'propPaymentReceipt');              //Generate Property Payment Receipt                                #API_ID = 011510
    Route::post('independent/get-holding-dues', 'getHoldingDues');          //Property/ Holding Dues                                           #API_ID = 011502
    Route::post('independent/generate-prop-orderid', 'generateOrderId');    //Generate Property Order ID                                       #API_ID = 011503
    Route::post('prop-payment-history', 'propPaymentHistory');              //Property Payment    History                                      #API_ID = 011513
    Route::post('prop-ulb-receipt', 'proUlbReceipt');                       //Property Ulb Payment Receipt                                     #API_ID = 011514
    Route::post('prop-comparative-demand', 'comparativeDemand');            //Property Comparative Demand                                      #API_ID = 011515
    Route::post('cluster/payment-history', 'clusterPaymentHistory');        //Cluster Payment History                                          #API_ID = 011516
    Route::post('cluster/payment-receipt', 'clusterPaymentReceipt');        //Generate Cluster Payment Receipt for Saf and Property            #API_ID = 011517
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

  Route::post('v1/building-rental-const', 'listBuildingRentalconst');      #API_ID = 012101
  Route::post('v1/get-forgery-type', 'listpropForgeryType');               #API_ID =012102                       
  Route::post('v1/get-rental-value', 'listPropRentalValue');               #API_ID = 012103
  Route::post('v1/building-rental-rate', 'listPropBuildingRentalrate');    #API_ID = 012104
  Route::post('v1/vacant-rental-rate', 'listPropVacantRentalrate');        #API_ID = 012105
  Route::post('v1/get-construction-list', 'listPropConstructiontype');     #API_ID = 012106
  Route::post('v1/floor-type', 'listPropFloor');                           #API_ID = 012107
  Route::post('v1/gb-building-usage-type', 'listPropgbBuildingUsagetype'); #API_ID = 012108
  Route::post('v1/gb-prop-usage-type', 'listPropgbPropUsagetype');         #API_ID = 012109
  Route::post('v1/prop-objection-type', 'listPropObjectiontype');          #API_ID = 012110
  Route::post('v1/prop-occupancy-factor', 'listPropOccupancyFactor');      #API_ID = 012111
  Route::post('v1/prop-occupancy-type', 'listPropOccupancytype');          #API_ID = 012112
  Route::post('v1/prop-ownership-type', 'listPropOwnershiptype');          #API_ID = 012113
  Route::post('v1/prop-penalty-type', 'listPropPenaltytype');              #API_ID = 012114
  Route::post('v1/prop-rebate-type', 'listPropRebatetype');                #API_ID = 012115
  Route::post('v1/prop-road-type', 'listPropRoadtype');                    #API_ID = 012116
  Route::post('v1/prop-transfer-mode', 'listPropTransfermode');            #API_ID = 012117
  Route::post('v1/get-prop-type', 'listProptype');                         #API_ID = 012118
  Route::post('v1/prop-usage-type', 'listPropUsagetype');                  #API_ID = 012119
});

/**
 * | Created On-31-01-2023 
 * | Created by-Mrinal Kumar
 * | Payment Cash Verification
 | Serial No. : 22
 */
Route::controller(CashVerificationController::class)->group(function () {
  Route::post('list-cash-verification', 'cashVerificationList');             #API_ID = 012201
  Route::post('verified-cash-verification', 'verifiedCashVerificationList'); #API_ID = 012202
  Route::post('tc-collections', 'tcCollectionDtl');                          #API_ID = 012203
  Route::post('verified-tc-collections', 'verifiedTcCollectionDtl');         #API_ID = 012204
  Route::post('verify-cash', 'cashVerify');                                  #API_ID = 012205
  Route::post('cash-receipt', 'cashReceipt');                                #API_ID = 012206
  Route::post('edit-chequedtl', 'editChequeNo');                             #API_ID = 012207
});

/**

 | Serial No. : 23
 */

Route::controller(BankReconcillationController::class)->group(function () {
  Route::post('search-transaction', 'searchTransaction');                   #API_ID = 012301
  Route::post('cheque-dtl-by-id', 'chequeDtlById');                         #API_ID = 012302
  Route::post('cheque-clearance', 'chequeClearance');                       #API_ID = 012303
});





#Added By Sandeep Bara
#Date 16/02/2023

// Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger', 'expireBearerToken']], function () {

/**
 * | Route Outside the Middleware
   | Serial No :24
 */
Route::controller(ReportController::class)->group(function () {
  Route::post('reports/property/collection', 'collectionReport');              #API_ID = 012401
  Route::post('reports/saf/collection', 'safCollection');                      #API_ID = 012402
  Route::post('reports/property/prop-saf-individual-demand-collection', 'safPropIndividualDemandAndCollection');                                     #API_ID = 012403
  Route::post('reports/saf/levelwisependingform', 'levelwisependingform');     #API_ID = 012404
  Route::post('reports/saf/levelformdetail', 'levelformdetail');               #API_ID = 012405
  Route::post('reports/saf/leveluserpending', 'levelUserPending');             #API_ID = 012406
  Route::post('reports/saf/userwiselevelpending', 'userWiseLevelPending');     #API_ID = 012407
  Route::post('reports/saf/userWiseWardWireLevelPending', 'userWiseWardWireLevelPending');  #API_ID = 012408
  Route::post('reports/saf/saf-sam-fam-geotagging', 'safSamFamGeotagging'); #API_ID = 012409        
  Route::post('reports/ward-wise-holding', 'wardWiseHoldingReport');        #API_ID = 012410
  Route::post('reports/list-fy', 'listFY');                                 #API_ID = 012411
  Route::post('reports/print-bulk-receipt', 'bulkReceipt');                 #API_ID = 012412
  Route::post('reports/property/gbsaf-collection', 'gbSafCollection');      #API_ID = 012413
  Route::post('reports/property/individual-demand-collection', 'propIndividualDemandCollection');  #API_ID = 012414
  Route::post('reports/property/gbsaf-individual-demand-collection', 'gbsafIndividualDemandCollection');                                       #API_ID = 012415
  Route::post('reports/not-paid-from-2016', 'notPaidFrom2016');             #API_ID = 012416
  Route::post('reports/previous-year-paid-not-current-year', 'previousYearPaidButnotCurrentYear');  #API_ID = 012417
  Route::post('reports/dcb-piechart', 'dcbPieChart');                       #API_ID = 012418
  Route::post('reports/prop/saf/collection', 'propSafCollection');          #API_ID = 012419
  Route::post('reports/rebate/penalty', 'rebateNpenalty');                  #API_ID = 012420
  Route::post('reports/property/payment-mode-wise-summery', 'PropPaymentModeWiseSummery');  #API_ID = 012421
  Route::post('reports/payment-mode-wise-summery', 'PaymentModeWiseSummery'); #API_ID = 012422
  Route::post('reports/saf/payment-mode-wise-summery', 'SafPaymentModeWiseSummery');  #API_ID = 012423
  Route::post('reports/property/dcb', 'PropDCB');                  #API_ID = 012424                
  Route::post('reports/property/ward-wise-dcb', 'PropWardWiseDCB');          #API_ID = 012425
  Route::post('reports/property/holding-wise-fine-rebate', 'PropFineRebate');  #API_ID = 012426         
  Route::post('reports/property/deactivated-list', 'PropDeactedList');  #API_ID = 012427                 
  Route::post('reports/mpl', 'mplReport');                                #API_ID = 012428
  Route::post('reports/mpl2', 'mplReport2');                              #API_ID = 012429
  Route::post('geo', 'getLocality');

  //written by prity pandey
  Route::post('report/mpl-todayCollection-new', 'mplReportCollectionNew'); #API_ID = 012430
  Route::post('report/ulb-list', 'ulbList'); #API_ID = 012430
  Route::post('live-dashboard-update', 'liveDashboardUpdate');
});
// });

//Written by Prity Pandey
Route::controller(ReportController::class)->group(function () {
  Route::post('reports/property/collection', 'collectionReport');
});
/**
    | Test Purpose
    | map locating 
| Serial No :17
 */
//written by prity pandey
Route::controller(MasterReferenceController::class)->group(function () {
  //construction type
  Route::post('ref-prop-construction-type-create', 'createConstructionType');
  Route::post('ref-prop-construction-type-update', 'updateConstructionType');
  Route::post('ref-prop-construction-type-get', 'constructiontypebyId');
  Route::post('ref-prop-construction-type-list', 'allConstructiontypelist');
  Route::post('ref-prop-construction-type-delete', 'deleteConstructionType');

  //floor type
  Route::post('ref-prop-floor-type-create', 'createFloorType');
  Route::post('ref-prop-floor-type-update', 'updateFloorType');
  Route::post('ref-prop-floor-type-get', 'floortypebyId');
  Route::post('ref-prop-floor-type-list', 'allFloortypelist');
  Route::post('ref-prop-floor-type-delete', 'deleteFloorType');

  //gbbuildingusagetype
  Route::post('ref-prop-gbbuilding-type-create', 'createGbBuildingType');
  Route::post('ref-prop-gbbuilding-type-update', 'updateGbBuildingType');
  Route::post('ref-prop-gbbuilding-type-get', 'GbBuildingtypebyId');
  Route::post('ref-prop-gbbuilding-type-list', 'allGbBuildingtypelist');
  Route::post('ref-prop-gbbuilding-type-delete', 'deleteGbBuildingType');

  //gbpropusagestype
  Route::post('ref-prop-gbpropusage-type-create', 'createGbPropUsageType');
  Route::post('ref-prop-gbpropusage-type-update', 'updateGbPropUsageType');
  Route::post('ref-prop-gbpropusage-type-get', 'GbPropUsagetypebyId');
  Route::post('ref-prop-gbpropusage-type-list', 'allGbPropUsagetypelist');
  Route::post('ref-prop-gbpropusage-type-delete', 'deleteGbPropUsageType');

  //objection type

  Route::post('ref-prop-objection-type-create', 'createObjectionType');
  Route::post('ref-prop-objection-type-update', 'updateObjectionType');
  Route::post('ref-prop-objection-type-get', 'ObjectiontypebyId');
  Route::post('ref-prop-objection-type-list', 'allObjectiontypelist');
  Route::post('ref-prop-objection-type-delete', 'deleteObjectionType');

  //occupancy factor
  Route::post('ref-prop-occupancy-factor-create', 'createOccupancyFactor');
  Route::post('ref-prop-occupancy-factor-update', 'updateOccupancyFactor');
  Route::post('ref-prop-occupancy-factor-get', 'OccupancyFactorbyId');
  Route::post('ref-prop-occupancy-factor-list', 'allOccupancyFactorlist');
  Route::post('ref-prop-occupancy-factor-delete', 'deleteOccupancyFactor');

  //occupancy type
  Route::post('ref-prop-occupancy-type-create', 'createOccupancyType');
  Route::post('ref-prop-occupancy-type-update', 'updateOccupancyType');
  Route::post('ref-prop-occupancy-type-get', 'OccupancyTypebyId');
  Route::post('ref-prop-occupancy-type-list', 'allOccupancyTypelist');
  Route::post('ref-prop-occupancy-type-delete', 'deleteOccupancyType');

  //ownership type
  Route::post('ref-prop-ownership-type-create', 'createOwnershipType');
  Route::post('ref-prop-ownership-type-update', 'updateOwnershipType');
  Route::post('ref-prop-ownership-type-get', 'OwnershipTypebyId');
  Route::post('ref-prop-ownership-type-list', 'allOwnershipTypelist');
  Route::post('ref-prop-ownership-type-delete', 'deleteOwnershipType');

  //road type
  Route::post('ref-prop-road-type-create', 'createRoadType');
  Route::post('ref-prop-road-type-update', 'updateroadType');
  Route::post('ref-prop-road-type-get', 'roadTypebyId');
  Route::post('ref-prop-road-type-list', 'allroadTypelist');
  Route::post('ref-prop-road-type-delete', 'deleteroadType');

  //property type
  Route::post('ref-prop-property-type-create', 'createPropertyType');
  Route::post('ref-prop-property-type-update', 'updatePropertyType');
  Route::post('ref-prop-property-type-get', 'propertyTypebyId');
  Route::post('ref-prop-property-type-list', 'allpropertyTypelist');
  Route::post('ref-prop-property-type-delete', 'deletepropertyType');

  //transfer mode  type
  Route::post('ref-prop-transfer-mode-create', 'createPropTransferMode');
  Route::post('ref-prop-transfer-mode-update', 'updateTransferMode');
  Route::post('ref-prop-transfer-mode-get', 'TransferModebyId');
  Route::post('ref-prop-transfer-mode-list', 'allTransferModelist');
  Route::post('ref-prop-transfer-mode-delete', 'deleteTransferMode');

  //prop usage type
  Route::post('ref-prop-usage-type-create', 'createPropUsageType');
  Route::post('ref-prop-usage-type-update', 'updateUsageType');
  Route::post('ref-prop-usage-type-get', 'PropUsageTypebyId');
  Route::post('ref-prop-usage-type-list', 'allPropUsageTypelist');
  Route::post('ref-prop-usage-type-delete', 'deletePropUsageType');

  //rebate type
  Route::post('ref-prop-rebate-type-create', 'createRebateType');
  Route::post('ref-prop-rebate-type-update', 'updateRebateType');
  Route::post('ref-prop-rebate-type-get', 'RebateTypebyId');
  Route::post('ref-prop-rebate-type-list', 'allRebateTypelist');
  Route::post('ref-prop-rebate-type-delete', 'deleteRebateType');


  //penalty type
  Route::post('ref-prop-penalty-type-create', 'createPenaltyType');
  Route::post('ref-prop-penalty-type-update', 'updatePenaltyType');
  Route::post('ref-prop-penalty-type-get', 'PenaltyTypebyId');
  Route::post('ref-prop-penalty-type-list', 'allPenaltyTypelist');
  Route::post('ref-prop-penalty-type-delete', 'deletePenaltyType');

  //m-forgery type
  Route::post('m-prop-forgery-type-create', 'createForgeryType');
  Route::post('m-prop-forgery-type-update', 'updateForgeryType');
  Route::post('m-prop-forgery-type-get', 'ForgeryTypebyId');
  Route::post('m-prop-forgery-type-list', 'allForgeryTypelist');
  Route::post('m-prop-forgery-type-delete', 'deleteForgeryType');


  //m-capital-value-rate  
  Route::post('m-capital-value-rate-get', 'MCapitalValurRateById');
  Route::post('m-capital-value-rate-list', 'allMCapitalValurRateList');


  //m-prop-building-reantal-const
  Route::post('m-prop-building-reantal-const-get', 'MPropBuildingRentalconstsById');
  Route::post('m-prop-building-reantal-const-list', 'allMPropBuildingRentalconstsList');

  //m-prop-building-reantal-const
  Route::post('m-prop-building-reantal-rate-get', 'MPropBuildingRentalRatesById');
  Route::post('m-prop-building-reantal-rate-list', 'allMPropBuildingRentalRatesList');

  //m-prop-cv-rate
  Route::post('m-prop-cv-rate-get', 'MPropCvRatesById');
  Route::post('m-prop-cv-rate-list', 'allMPropCvRatesList');

  //m-prop-multi-factor
  Route::post('m-prop-multi-factor-get', 'MPropMultiFactorById');
  Route::post('m-prop-multi-factor-list', 'allMPropMultiFactorList');

  
  //m-prop-rental-value
  Route::post('m-prop-rental-value-get', 'MPropRentalValueById');
  Route::post('m-prop-rental-value-list', 'allMPropRentalValueList');

  //m-prop-road-types
  Route::post('m-prop-road-types-get', 'MPropRoadTypeById');
  Route::post('m-prop-road-types-list', 'allMPropRoadTypeList');

  //m-prop-vacant-rentalrates
  Route::post('m-prop-vacant-rentalrates-get', 'MPropVacantRentalrateById');
  Route::post('m-prop-vacant-rentalrates-list', 'allMPropVacantRentalrateList');

  
  //m-slider
  Route::post('m-slider-create', 'createSlider');
  Route::post('m-slider-update', 'updateSlider');
  Route::post('m-slider-get', 'allSliderList');
  Route::post('m-slider-delete', 'deleteSlider');
  Route::post('m-slider-get-by-id', 'sliderById');

  //m-assets
  Route::post('m-assets-create', 'addAssetesv2');
  Route::post('m-assets-update', 'editAssetesv2');
  Route::post('m-assets-get', 'allListAssetesv2');
  Route::post('m-assets-delete', 'deleteAssetesv2');
});




//====================================NOT IN USE===============================================//
/**
 * | Property Calculator
       | Serial No : 03 
 */
Route::controller(SafCalculatorController::class)->group(function () {
  // Route::post('saf-calculation', 'calculateSaf');                  #API_ID = 010301
});

/**
 * | PropertyBifurcation Process
 * | Crated By - Sandeep Bara
 * | Created On- 23-11-2022
     | Serial No : 05
 */
Route::controller(PropertyBifurcationController::class)->group(function () {
  Route::post('searchByHoldingNoBi', "readHoldigbyNo");                       #API_ID = 010401
  Route::match(["POST", "GET"], 'applyBifurcation/{id}', "addRecord");        #API_ID = 010501
  Route::post('bifurcationInbox', "inbox");                                   #API_ID = 010503
  Route::post('bifurcationOutbox', "outbox");                                 #API_ID = 010504
  Route::post('bifurcationPostNext', "postNextLevel");                        #API_ID = 010505
  Route::get('getSafDtls/{id}', "readSafDtls");                               #API_ID = 010506
  Route::match(["get", "post"], 'documentUpload/{id}', 'documentUpload');     #API_ID = 010507
});


/**
 * | Property Document Operation
     | Serial No : 11
 */
Route::controller(DocumentOperationController::class)->group(function () {
  Route::post('get-all-documents', 'getAllDocuments');                        #API_ID = 011101
});


/**
 * | Property Cluster
 * | Created By - Sam kerketta
 * | Created On- 23-11-2022 
       | Serial No : 10
 */
Route::controller(ClusterController::class)->group(function () {


  Route::post('cluster/property-by-cluster', 'propertyByCluster');            #API_ID = 011008

});

//written by prity pandey
#Route for getting citizen details based on citizen Id
/**
| Serial No :26
 */
Route::controller(CitizenController::class)->group(function () {
  Route::post('citizen/details', 'citizenDetailsWithCitizenId')->middleware(['json.response', 'auth_maker']);                                                         #API_ID = 012601

  Route::post('citizen/property-count', 'propertyCount');
});

  #Added By Prity Pandey
  #Date 31/10/2023
  #Route for getting location based ward list 
  /**
| Serial No :25
   */
  Route::controller(LocationController::class)->group(function () {
    Route::post('location', 'location_list');                               #API_ID = 012501
    Route::post('location/ward-list', 'bindLocationWithWards');             #API_ID = 012502
    Route::post('map/level1', 'mapLevel1');                                 #API_ID = 012503
  });