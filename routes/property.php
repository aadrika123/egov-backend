<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Property\ActiveSafController;
use App\Http\Controllers\Property\ConcessionController;
use App\Http\Controllers\Property\ObjectionController;
use App\Http\Controllers\Property\SafCalculatorController;
use App\Http\Controllers\ObjectionController as NewObjectionController;
use App\Http\Controllers\Property\PropertyDeactivateController;
use App\Http\Controllers\Property\SafReassessmentController;

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
        Route::post('getProperty', 'getPropIdByWardNoHodingNo');                                            // get Property (search) by ward no and holding no
        Route::match(["get", "post"], 'ulb/workflow/member', 'setWorkFlowForwordBackword');                 // get Property (search) by ward no and holding no
        Route::post('saf/calculate-by-saf-id', 'calculateSafBySafId');                                      // Calculate SAF By SAF ID
        Route::post('saf/generate-order-id', 'generateOrderId');                                            // Generate Order ID
        Route::post('saf/saf-payment', 'paymentSaf');                                                       // SAF Payment
        Route::get('saf/prop-transactions', 'getPropTransactions');                                         // Get Property Transactions
    });

    // SAF Reassessment
    Route::controller(SafReassessmentController::class)->group(function () {
    });

    // Property Calculator
    Route::controller(SafCalculatorController::class)->group(function () {
        Route::post('saf-calculation', 'calculateSaf');
    });

    //Property Concession
    Route::controller(ConcessionController::class)->group(function () {
        Route::post('concession/UpdateConDetail', 'UpdateConDetail');
        Route::post('concession/applyConcession', 'applyConcession');
        Route::post('concession/postHolding', 'postHolding');
    });

    //Property Objection
    Route::controller(NewObjectionController::class)->group(function () {
        Route::post('objection/ClericalMistake/{id}', 'ClericalMistake');
        Route::post('objection/ownerDetails', 'getOwnerDetails');
        Route::post('objection/rectification', 'rectification');
    });
    //Property Deactivation
    /**
     * Crated By - Sandeep Bara
     * Created On- 19-11-2022 
     */
    Route::controller(PropertyDeactivateController::class)->group(function () {
        Route::post('searchByHoldingNo', "readHoldigbyNo");
        Route::match(["POSt", "GET"], 'deactivationRequest/{id}', "deactivatProperty");
    });
});
