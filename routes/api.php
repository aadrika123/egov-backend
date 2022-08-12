<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ApiMasterController;
use App\Http\Controllers\CitizenController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SelfAdvertisementController;
use App\Http\Controllers\UlbController;
use App\Http\Controllers\UlbWorkflowController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\WorkflowTrackController;
use App\Http\Controllers\ActiveSafController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/**
 * Creation Date:-24-06-2022
 * Created By:- Anshu Kumar
 * -------------------------------------------------------------------------
 * Code Test
 * -------------------------------------------------------------------------
 * Code Tested By-Anil Mishra Sir
 * Code Testing Date:-25-06-2022 
 * -------------------------------------------------------------------------
 */

// Route Used for Login and Register the User
Route::controller(UserController::class)->group(function () {
    Route::post('login', 'loginAuth')->middleware('request_logger');
    Route::post('register', 'store');
    Route::put('edit-user/{id}', 'update');
    Route::delete('delete-user', 'deleteUser');
    Route::get('get-user/{id}', 'getUser');
    Route::get('get-all-users', 'getAllUsers');
});

/**
 * Routes for API masters, api searchable and api edit
 * CreatedOn-29-06-2022
 */

Route::controller(ApiMasterController::class)->group(function () {
    Route::post('save-api', 'store');
    Route::put('edit-api/{id}', 'update');
    Route::get('get-api-by-id/{id}', 'getApiByID');
    Route::get('get-all-apis', 'getAllApis');
    Route::post('search-api', 'search');
    Route::get('search-api-by-tag', 'searchApiByTag');
});

/**
 * | Citizen Registration
 * | Created On-08-08-2022 
 */
Route::controller(CitizenController::class)->group(function () {
    Route::post('citizen-register', 'citizenRegister');         // Citizen Registration
});

// Inside Middleware Routes with API Authenticate 
Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {
    /**
     * Routes for User 
     * Created By-Anshu Kumar
     * Created On-20-06-2022 
     * Modified On-27-06-2022 
     */
    Route::controller(UserController::class)->group(function () {
        Route::get('test', 'testing');
        Route::post('logout', 'logOut');
        Route::post('change-password', 'changePass');

        // Route are authorized for super admin only using Middleware 
        Route::group(['middleware' => ['can:isSuperAdmin']], function () {
            // Route::put('edit-user/{id}', 'update');
            // Route::delete('delete-user', 'deleteUser');
            // Route::get('get-user/{id}', 'getUser');
            // Route::get('get-all-users', 'getAllUsers');
        });
    });

    /**
     * Route for Roles
     * Created By-Anshu Kumar
     * Created Date-27-06-2022
     */
    Route::controller(RoleController::class)->group(function () {
        // Route are authorized for super admin only using Middleware 
        Route::group(['middleware' => ['can:isSuperAdmin']], function () {
            Route::post('save-role', 'storeRole');
            Route::put('edit-role/{id}', 'editRole');
            Route::get('get-role/{id}', 'getRole');

            Route::post('role-menu', 'roleMenu');
            Route::put('edit-role-menu/{id}', 'editRoleMenu');
            Route::get('get-role-menu/{id}', 'getRoleMenu');

            Route::post('role-user', 'roleUser');
            Route::put('edit-role-user/{id}', 'editRoleUser');
            Route::get('get-role-user/{id}', 'getRoleUser');

            Route::post('role-menu-logs', 'roleMenuLogs');
            Route::put('edit-role-menu-logs/{id}', 'editRoleMenuLogs');
            Route::get('get-role-menu-logs/{id}', 'getRoleMenuLogs');

            Route::post('role-user-logs', 'roleUserLogs');
            Route::put('edit-role-user-logs/{id}', 'editRoleUserLogs');
            Route::get('get-role-user-logs/{id}', 'getRoleUserLogs');
        });
    });

    /**
     * Routes for Ulbs
     * Created By-Anshu Kumar
     * Creation Date-02-07-2022 
     * Modified On-
     */
    Route::controller(UlbController::class)->group(function () {
        Route::post('save-ulb', 'store');
        Route::put('edit-ulb/{id}', 'edit');
        Route::get('get-ulb/{id}', 'view');
        Route::get('get-all-ulb', 'getAllUlb');
        Route::delete('delete-ulb/{id}', 'deleteUlb');
    });

    /**
     * Routes for Workflows
     * Created By-Anshu Kumar
     * Creation Date-06-07-2022 
     * Modified On-
     */
    Route::controller(WorkflowController::class)->group(function () {
        Route::post('add-workflow', 'storeWorkflow');
        Route::get('view-workflow/{id}', 'viewWorkflow');
        Route::put('edit-workflow/{id}', 'updateWorkflow');
        Route::delete('delete-workflow/{id}', 'deleteWorkflow');
        Route::get('all-workflows', 'getAllWorkflows');

        Route::post('workflow-candidate', 'storeWorkflowCandidate');
        Route::get('view-workflow-candidates/{id}', 'viewWorkflowCandidates');
        Route::get('all-workflow-candidates', 'allWorkflowCandidates');
        Route::put('edit-workflow-candidates/{id}', 'editWorkflowCandidates');
        Route::delete('delete-workflow-candidates/{id}', 'deleteWorkflowCandidates');
        Route::get('gen/workflow/workflow-candidates/{ulbworkflowid}', 'getWorkflowCandidatesByUlbWorkflowID');
    });

    /**
     * APIs for Module Master
     * Created By-Anshu Kumar
     * Creation Date-14-07-2022
     * Modified By-
     */
    Route::resource('crud/module-masters', ModuleController::class);

    /**
     * Api route for Ulb Module Master
     * CreatedBy-Anshu Kumar
     * Creation Date-14-07-2022 
     * Modified By-
     */
    Route::resource('crud/ulb-workflow-masters', UlbWorkflowController::class);

    // Get Ulb Workflow details by Ulb Ids
    Route::get('admin/workflows/{ulb_id}', [UlbWorkflowController::class, 'getUlbWorkflowByUlbID']);

    // Workflow Track
    Route::controller(WorkflowTrackController::class)->group(function () {
        Route::post('save-workflow-track', 'store');                                       // Save Workflow Track Messages
        Route::get('get-workflow-track/{id}', 'getWorkflowTrackByID');                     // Get Workflow Track Message By TrackID
        Route::get('gen/workflow-track/{RefTableID}/{RefTableValue}', 'getWorkflowTrackByTableIDValue');                     // Get WorkflowTrack By TableRefID and RefTableValue
    });

    // Advertisement Updates, Fetch data
    Route::controller(SelfAdvertisementController::class)->group(function () {
        Route::put('crud/update-selfadvertisement/{id}', 'updateSelfAdvertisement');       // Update Self Advertisement
        Route::get('crud/get-selfadvertisement-by-id/{id}', 'getSelfAdvertisementByID');   // Get Self Advertisement By Id
        Route::get('crud/get-all-selfadvertisements', 'getAllSelfAdvertisements');         // Get All Self Advertisement Datas
        Route::delete('crud/del-selfadvertisement/{id}', 'deleteSelfAdvertisement');       // Delete Self Advertisement By ID
    });

    // Citizen Register
    Route::controller(CitizenController::class)->group(function () {
        Route::get('get-citizen-by-id/{id}', 'getCitizenByID');     // Get Citizen By ID
        Route::get('get-all-citizens', 'getAllCitizens');           // Get All Citizens
        Route::post('edit-citizen-by-id/{id}', 'editCitizenByID');         // Approve Or Reject Citizen by Id
    });

    /**
     * ----------------------------------------------------------------------------------------
     * | Property Module Routes
     * ----------------------------------------------------------------------------------------
     */

    // SAF 
    Route::controller(ActiveSafController::class)->group(function () {
        Route::post('apply-for-saf', 'applySaf');                               // Applying Saf Route
        Route::get('get-saf-candidates-by-workflow-id/{id}', 'getSafCandByWorkflowId');  // Get SAF Candidates by WorkflowID
        Route::get('saf-inbox/{id?}', 'inbox');                             // Saf workflow Inbox and Inbox By ID
        Route::get('saf-outbox/{id?}', 'outbox');                           // Saf Workflow Outbox and Outbox By ID
    });
});

Route::group(['middleware' => ['cors', 'json.response', 'request_logger']], function () {
    /**
     * API's for Self Advertisement Save
     */
    Route::controller(SelfAdvertisementController::class)->group(function () {
        Route::post('crud/store-selfadvertisement', 'storeSelfAdvertisement');      // Save Self Advertisement
    });
});
