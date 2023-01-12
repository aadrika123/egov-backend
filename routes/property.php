<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Property\ActiveSafController;
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
use App\Http\Controllers\Property\SafDemandController;
use App\Http\Controllers\Property\DocumentController;
use App\Http\Controllers\CustomController;
use App\Http\Controllers\property\ClusterController;

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
    Route::post('saf/calculate-by-saf-id', 'calculateSafBySafId');                                      // Calculate SAF By SAF ID(13)
    Route::post('saf/generate-order-id', 'generateOrderId');                                            // Generate Order ID(14)
    Route::post('saf/saf-payment', 'paymentSaf');                                                       // SAF Payment(15)
    Route::post('saf/payment-receipt', 'generatePaymentReceipt');                                       // Generate payment Receipt(16)
    Route::get('saf/prop-transactions', 'getPropTransactions');                                         // Get Property Transactions(17)

    Route::post('saf/site-verification', 'siteVerification');                                           // Ulb TC Site Verification(18)
    Route::post('saf/geotagging', 'geoTagging');                                                        // Geo Tagging(19)
    Route::post('saf/get-tc-verifications', 'getTcVerifications');                                      // Get TC Verifications  Data(20)
    Route::post('saf/doc-status', 'safDocStatus');                                                      // Doc Verify (21)
    Route::post('saf/proptransaction-by-id', 'getTransactionBySafPropId');                              // Get Property Transaction by Property ID or SAF id(22)
  });

  /**
   * | SAF Demand and Property contollers
       | Serial No : 02
   */
  Route::controller(SafDemandController::class)->group(function () {
    Route::post('saf/get-demand-by-id', 'getDemandBySafId');                // <------------- Get the demandable Amount of the Property after payment done
  });

  /**
   * | SAF Reassessment
       | Serial No : 03
   */
  Route::controller(SafReassessmentController::class)->group(function () {
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
    Route::match(["POST", "GET"], 'deactivationRequest/{id}', "deactivatProperty");
    Route::post('inboxDeactivation', "inbox");
    Route::post('outboxDeactivation', "outbox");
    Route::post('postNextDeactivation', "postNextLevel");
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
    Route::post('get-doc-list', 'getDocList');
    Route::post('upload-document', 'safDocumentUpload');
    Route::post('getSafUploadDocuments', 'getUploadDocuments');
  });

  /**
   * | Property Concession
       | Serial No : 07
   */
  Route::controller(ConcessionController::class)->group(function () {
    Route::post('concession/apply-concession', 'applyConcession');      //01                
    Route::post('concession/postHolding', 'postHolding');               //02  
    Route::post('concession/inbox', 'inbox');                           //03               // Concession Inbox 
    Route::post('concession/outbox', 'outbox');                         //04               // Concession Outbox
    Route::post('concession/details', 'getDetailsById');                //05               // Get Concession Details by ID
    Route::post('concession/escalate', 'escalateApplication');          //06               // escalate application
    Route::post('concession/special-inbox', 'specialInbox');            //07               // escalated application inbox
    Route::post('concession/btc-inbox', 'btcInbox');                    //17               // Back To Citizen Inbox

    Route::post('concession/next-level', 'postNextLevel');              //08               // Backward Forward Application
    Route::post('concession/approvalrejection', 'approvalRejection');   //09               // Approve Reject Application
    Route::post('concession/backtocitizen', 'backToCitizen');           //10               // Back To Citizen 
    Route::post('concession/owner-details', 'getOwnerDetails');         //11

    Route::post('concession/list', 'concessionList');                   //12
    Route::post('concession/list-id', 'concessionByid');                //13
    Route::post('concession/doc-list', 'concessionDocList');            //14
    Route::post('concession/doc-upload', 'concessionDocUpload');        //15
    Route::post('concession/doc-status', 'concessionDocStatus');        //16
  });


  /**
   * | Property Objection
       | Serial No : 08
   */
  Route::controller(ObjectionController::class)->group(function () {
    Route::post('objection/apply-objection', 'applyObjection');          //01
    Route::get('objection/objection-type', 'objectionType');             //02                      
    Route::post('objection/owner-details', 'ownerDetails');              //03
    Route::post('objection/assesment-details', 'assesmentDetails');      //04

    Route::post('objection/inbox', 'inbox');                              //05        //Inbox
    Route::post('objection/outbox', 'outbox');                            //06        //Outbox
    Route::post('objection/details', 'getDetailsById');                  //07
    Route::post('objection/post-escalate', 'postEscalate');              //08        // Escalate the application and send to special category
    Route::post('objection/special-inbox', 'specialInbox');               //09        // Special Inbox 
    Route::post('objection/next-level', 'postNextLevel');                //10
    Route::post('objection/approvalrejection', 'approvalRejection');     //11
    Route::post('objection/backtocitizen', 'backToCitizen');             //12

    Route::get('objection/list', 'objectionList');                       //13
    Route::post('objection/list-id', 'objectionByid');                   //14
    Route::post('objection/doc-list', 'objectionDocList');               //15
    Route::post('objection/doc-upload', 'objectionDocUpload');           //16
    Route::post('objection/doc-status', 'objectionDocStatus');           //17
  });

  /**
   * | for custom details
       | Serial No : 09
   */
  Route::controller(CustomController::class)->group(function () {
    Route::post('get-all-custom-tab-data', 'getCustomDetails');
    Route::post('post-custom-data', 'postCustomDetails');
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
    Route::post('get-harvesting-list', 'waterHarvestingList');                  //03
    Route::post('harvesting-list-id', 'harvestingListById');                    //04
    Route::post('harvesting-doc-id', 'harvestingDocList');                      //05
    Route::post('harvesting-doc-upload', 'docUpload');                          //06
    Route::post('harvesting-doc-status', 'docStatus');                          //07
    Route::post('harvesting/inbox', 'harvestingInbox');                         //08
    Route::post('harvesting/outbox', 'harvestingOutbox');                       //09
    Route::post('harvesting/next-level', 'postNextLevel');                      //10
    Route::post('harvesting/approval-rejection', 'finalApprovalRejection');     //11
    Route::post('harvesting/rejection', 'rejectionOfHarvesting');               //12
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
    Route::post('cluster/get-cluster-by-id', 'getClusterById');
    Route::post('cluster/edit-cluster-details', 'editClusterDetails');
    Route::post('cluster/save-cluster-details', 'saveClusterDetails');
    Route::post('cluster/delete-cluster-data', 'deleteClusterData');
    # cluster maping
    Route::post('cluster/details-by-holding', 'detailsByHolding');
    Route::post('cluster/holding-by-cluster', 'holdingByCluster');
    Route::post('cluster/save-holding-in-cluster', 'saveHoldingInCluster');
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
    Route::post('get-filter-property-details', 'getFilterProperty');
    Route::post('get-filter-safs-details', 'getFilterSafs');
    Route::get('get-list-saf', 'getListOfSaf');
    Route::post('active-application/get-user-details', 'getUserDetails');
  });
});
