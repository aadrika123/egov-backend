<?php

use App\Http\Controllers\Cluster\ClusterController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Property\ActiveSafController;
use App\Http\Controllers\Property\ConcessionController;
use App\Http\Controllers\Property\SafCalculatorController;
use App\Http\Controllers\Property\CalculatorController;
use App\Http\Controllers\Property\ObjectionController;
use App\Http\Controllers\Property\PropertyDeactivateController;
use App\Http\Controllers\Property\RainWaterHarvestingController;
use App\Http\Controllers\Property\SafReassessmentController;
use App\Http\Controllers\Property\PropertyBifurcationController;
use App\Http\Controllers\Property\PropMaster;
use App\Http\Controllers\Property\PropertyDetailsController;

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
    // SAF 
    Route::controller(ActiveSafController::class)->group(function () {
        Route::post('saf/apply', 'applySaf');                                                               // Applying Saf Route
        Route::post('saf/doc-upload', 'documentUpload');                                                    // Document Upload by Citizen or JSK
        Route::post('saf/verifydoc', 'verifyDoc');                                                         // Verify Uploaded Document by DA
        Route::get('saf/master-saf', 'masterSaf');                                                          // Get all master data in Saf
        Route::get('saf/inbox', 'inbox');                                                                   // Saf Inbox
        Route::get('saf/outbox', 'outbox');                                                                 // Saf Workflow Outbox and Outbox By search key
        Route::post('saf-details', 'details');                                                              // Saf Workflow safDetails and safDetails By ID
        Route::post('saf/candidates', 'getSafCandidates');                                                  // Get SAF Candidates
        Route::post('saf/escalate', 'postEscalate');                                                        // Saf Workflow special and safDetails By id
        Route::get('saf/escalate/inbox/{key?}', 'specialInbox');                                            // Saf workflow Inbox and Inbox By search key
        Route::post('saf/independent-comment', 'commentIndependent');                                       // Independent Comment for SAF Application
        Route::post('saf/post/level', 'postNextLevel');                                                     // Forward or Backward Application
        Route::post('saf/approvalrejection', 'approvalRejectionSaf');                                       // Approval Rejection SAF Application
        Route::post('saf/back-to-citizen', 'backToCitizen');                                                // Saf Application Back To Citizen
        Route::post('saf/get-prop-byholding', 'getPropByHoldingNo');                                        // get Property (search) by ward no and holding no
        Route::match(["get", "post"], 'ulb/workflow/member', 'setWorkFlowForwordBackword');                 // get Property (search) by ward no and holding no
        Route::post('saf/calculate-by-saf-id', 'calculateSafBySafId');                                      // Calculate SAF By SAF ID
        Route::post('saf/generate-order-id', 'generateOrderId');                                            // Generate Order ID
        Route::post('saf/saf-payment', 'paymentSaf');                                                       // SAF Payment
        Route::get('saf/prop-transactions', 'getPropTransactions');                                         // Get Property Transactions

        Route::post('saf/site-verification', 'siteVerification');                                           // Ulb TC Site Verification
        Route::post('saf/geotagging', 'geoTagging');                                                         // Geo Tagging
    });

    // SAF Reassessment
    Route::controller(SafReassessmentController::class)->group(function () {
    });

    Route::controller(CalculatorController::class)->group(function () {
        Route::post('get-dashboard', 'dashboardDate');
    });

    // Property Calculator
    Route::controller(SafCalculatorController::class)->group(function () {
        Route::post('saf-calculation', 'calculateSaf');
    });

    Route::controller(CalculatorController::class)->group(function () {
        Route::post('get-dashboard', 'dashboardDate');
    });
    //Property Deactivation
    /**
     * Crated By - Sandeep Bara
     * Created On- 19-11-2022 
     */
    Route::controller(PropertyDeactivateController::class)->group(function () {
        Route::post('searchByHoldingNo', "readHoldigbyNo");
        Route::match(["POST", "GET"], 'deactivationRequest/{id}', "deactivatProperty");
        Route::post('inboxDeactivation', "inbox");
        Route::post('outboxDeactivation', "outbox");
        Route::post('postNextDeactivation', "postNextLevel");
        Route::post('getDeactivationDtls', "readDeactivationReq");
    });
    //PropertyBifurcation Process
    /**
     * Crated By - Sandeep Bara
     * Created On- 23-11-2022 
     */
    Route::controller(PropertyBifurcationController::class)->group(function () {
        Route::post('searchByHoldingNoBi', "readHoldigbyNo");
        Route::match(["POST", "GET"], 'applyBifurcation/{id}', "addRecord");
    });


    //Property Concession
    Route::controller(ConcessionController::class)->group(function () {
        Route::post('concession/apply-concession', 'applyConcession');
        Route::post('concession/postHolding', 'postHolding');
        Route::get('concession/inbox', 'inbox');                                               // Concession Inbox 
        Route::get('concession/outbox', 'outbox');                                             // Concession Outbox
        Route::post('concession/details', 'getDetailsById');                                   // Get Concession Details by ID
        Route::post('concession/escalate', 'escalateApplication');                             // escalate application
        Route::get('concession/special-inbox', 'specialInbox');                                // escalated application inbox
        Route::post('concession/owner-details', 'getOwnerDetails');

        Route::post('concession/next-level', 'postNextLevel');                                  // Backward Forward Application
        Route::post('concession/approvalrejection', 'approvalRejection');                       // Approve Reject Application
        Route::post('concession/backtocitizen', 'backToCitizen');                                // Back To Citizen 
    });


    //Property Objection
    Route::controller(ObjectionController::class)->group(function () {
        Route::post('objection/apply-objection', 'applyObjection');
        Route::get('objection/objection-type', 'objectionType');
        Route::get('objection/owner-details', 'ownerDetails');
        Route::post('objection/assesment-details', 'assesmentDetails');

        Route::get('objection/inbox', 'inbox');
        Route::get('objection/outbox', 'outbox');
        Route::post('objection/details', 'getDetailsById');
        Route::post('objection/post-escalate', 'postEscalate');                                 // Escalate the application and send to special category
        Route::get('objection/special-inbox', 'specialInbox');                                   // Special Inbox 
        Route::post('objection/next-level', 'postNextLevel');
        Route::post('objection/approvalrejection', 'approvalRejection');
        Route::post('objection/backtocitizen', 'backToCitizen');
    });

    Route::controller(CalculatorController::class)->group(function () {
        Route::post('get-dashboard', 'dashboardDate');
    });


    //Property Deactivation
    /**
     * Crated By - Sandeep Bara
     * Created On- 19-11-2022 
     */
    Route::controller(PropertyDeactivateController::class)->group(function () {
        Route::post('searchByHoldingNo', "readHoldigbyNo");
        Route::match(["POST", "GET"], 'deactivationRequest/{id}', "deactivatProperty");
        Route::post('inboxDeactivation', "inbox");
        Route::post('postNextDeactivation', "postNextLevel");
        Route::post('getDeactivationDtls', "readDeactivationReq");
    });


    //PropertyBifurcation Process
    /**
     * Crated By - Sandeep Bara
     * Created On- 23-11-2022 
     */
    Route::controller(PropertyBifurcationController::class)->group(function () {
        Route::post('searchByHoldingNoBi', "readHoldigbyNo");
    });


    //Rain water Harvesting
    /**
     * Crated By - Sam kerketta
     * Created On- 22-11-2022 
     */
    Route::controller(RainWaterHarvestingController::class)->group(function () {
        Route::get('get-wardmaster-data', 'getWardMasterData');
        Route::post('water_harvesting_application', 'waterHarvestingApplication');
    });

    // Property Cluster
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


    Route::controller(PropMaster::class)->group(function () {
        Route::get('prop-usage-type', 'propUsageType');
        Route::get('prop-const-type', 'propConstructionType');
        Route::get('prop-occupancy-type', 'propOccupancyType');
        Route::get('prop-property-type', 'propPropertyType');
        Route::get('prop-road-type', 'propRoadType');
        // Property Details
        Route::controller(PropertyDetailsController::class)->group(function () {
            Route::post('get-filter-property-details', 'getFilterProperty');
        });
    });

    Route::controller(CalculatorController::class)->group(function () {
        Route::post('calculatePropertyTax', 'calculator');
    });
});
