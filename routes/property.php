<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Property\ActiveSafController;
use App\Http\Controllers\Property\ObjectionController;
use App\Http\Controllers\Property\SafCalculatorController;

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
        Route::match(["get", "post"], 'saf/apply/{assessmentType}', 'applySaf');                            // Applying Saf Route
        //Route::get('saf/inbox/{key?}', 'inbox');                             // Saf workflow Inbox and Inbox By search key
        Route::get('saf/inbox', 'inbox');                                      // Saf Inbox
        Route::get('saf/outbox', 'outbox');                                    // Saf Workflow Outbox and Outbox By search key
        Route::post('saf-details', 'details');                                 // Saf Workflow safDetails and safDetails By ID
        Route::post('saf/candidates', 'getSafCandidates');                     // Get SAF Candidates
        Route::post('saf/escalate', 'postEscalate');                           // Saf Workflow special and safDetails By id
        Route::get('saf/escalate/inbox/{key?}', 'specialInbox');               // Saf workflow Inbox and Inbox By search key
        Route::post('saf/independent-comment', 'postIndependentComment');      // Independent Comment for SAF Application
        Route::post('saf/post/level', 'postNextLevel');                        // Forward or Backward Application
        Route::post('getProperty', 'getPropIdByWardNoHodingNo');               // get Property (search) by ward no and holding no
        Route::match(["get", "post"], 'ulb/workflow/member', 'setWorkFlowForwordBackword');      // get Property (search) by ward no and holding no
    });
    //Property Objection
    Route::controller(ObjectionController::class)->group(function () {
        Route::match(["get", "post"], 'property-objection/{id}', 'propertyObjection'); // Objection Workflow Apply By id
        Route::get('objection/inbox/{key?}', 'propObjectionInbox');          // Objection Workflow Inbox  By key
        Route::get('objection/outbox/{key?}', 'propObjectionOutbox');        // Objection Workflow Outbox  By key
        Route::get('objection/escalate/inbox/{key?}', 'specialObjectionInbox');        // Objection Workflow special Inbox  By key
    });
});

Route::controller(SafCalculatorController::class)->group(function () {
    Route::post('saf-calculation', 'safCalculation');
});
