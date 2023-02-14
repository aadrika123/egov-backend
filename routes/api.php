<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ApiMasterController;
use App\Http\Controllers\CitizenController;
use App\Http\Controllers\CustomController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\Menu\MenuController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\SelfAdvertisementController;
use App\Http\Controllers\UlbController;
use App\Http\Controllers\UlbMaster;
use App\Http\Controllers\UlbWorkflowController;
use App\Http\Controllers\Workflows\WorkflowController;
use App\Http\Controllers\Workflows\WorkflowTrackController;
use App\Http\Controllers\Ward\WardController;
use App\Http\Controllers\WcController;
use App\Http\Controllers\WorkflowMaster\MasterController;
use App\Http\Controllers\WorkflowMaster\RoleController;
use App\Http\Controllers\WorkflowMaster\WardUserController;
use App\Http\Controllers\Workflows\UlbWorkflowRolesController;
use App\Http\Controllers\WorkflowMaster\WorkflowMap;
use App\Http\Controllers\WorkflowMaster\WorkflowRoleController;
use App\Http\Controllers\WorkflowMaster\WorkflowWardUserController;
use App\Http\Controllers\WorkflowMaster\WorkflowRoleUserMapController;
use App\Http\Controllers\WorkflowMaster\WorkflowRoleMapController;
use App\Http\Controllers\WorkflowMaster\WorkflowController as WfController;
use App\Http\Controllers\Workflows\WfDocumentController;

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
/**
 * | Updated By- Sam kerketta
 */
Route::controller(UserController::class)->group(function () {
    Route::post('login', 'loginAuth')->middleware('request_logger');
    Route::post('register', 'store');
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
    Route::post('citizen-login', 'citizenLogin')->middleware('request_logger');
    Route::post('citizen-logout', 'citizenLogout')->middleware('auth:sanctum');
});

/**
 * | Created On-147-08-2022 
 * | Created By-Anshu Kumar
 * | Get all Ulbs by Ulb ID
 */
Route::controller(UlbController::class)->group(function () {
    Route::get('get-all-ulb', 'getAllUlb');
    Route::post('city/state/ulb-id', 'getCityStateByUlb');
});

// Inside Middleware Routes with API Authenticate 
Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {
    /**
     * Routes for User 
     * Created By-Anshu Kumar
     * Updated By-Sam Kerketta
     * Created On-20-06-2022 
     * Modified On-27-06-2022 
     */
    Route::controller(UserController::class)->group(function () {
        Route::post('authorised-register', 'authorizeStore');             // authorised user adding user // 
        Route::get('test', 'testing');
        Route::post('logout', 'logOut');
        Route::post('change-password', 'changePass');

        // User Profile APIs
        Route::get('my-profile-details', 'myProfileDetails');   // For get My profile Details
        Route::post('edit-my-profile', 'editMyProfile');        // For Edit My profile Details ---->>edited by mrinal method changed from put to post

        Route::post('edit-user', 'update');
        Route::post('delete-user', 'deleteUser');
        Route::get('get-user/{id}', 'getUser');
        Route::get('get-all-users', 'getAllUsers');
        Route::post('list-employees', 'employeeList');

        // Route are authorized for super admin only using Middleware 
        Route::group(['middleware' => ['can:isSuperAdmin']], function () {
            // Route::put('edit-user/{id}', 'update');
            // Route::delete('delete-user', 'deleteUser');
            // Route::get('get-user/{id}', 'getUser');
            // Route::get('get-all-users', 'getAllUsers');
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
        // Route::get('get-all-ulb', 'getAllUlb');
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
        Route::get('gen/workflow/workflow-candidates/{ulbworkflowid}', 'getWorkflowCandidatesByUlbWorkflowID');  // Get Workflow Candidates by ulb-workflow-id
    });

    // Workflow Roles Rest Apis
    Route::resource('workflow/workflow-roles', UlbWorkflowRolesController::class);

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
        Route::post('save-workflow-track', 'store');                                                                         // Save Workflow Track Messages
        Route::post('get-workflow-track', 'getWorkflowTrackByID');                                                       // Get Workflow Track Message By TrackID
        Route::post('gen/workflow-track', 'getWorkflowTrackByTableIDValue');                     // Get WorkflowTrack By TableRefID and RefTableValue

        //changes by mrinal
        Route::post('workflow-track/getNotificationByCitizenId', 'getNotificationByCitizenId');
    });

    // Citizen Register
    Route::controller(CitizenController::class)->group(function () {
        Route::get('get-citizen-by-id/{id}', 'getCitizenByID');                                                // Get Citizen By ID
        Route::get('get-all-citizens', 'getAllCitizens');                                                      // Get All Citizens
        Route::post('edit-citizen-profile', 'citizenEditProfile');                                             // Approve Or Reject Citizen by Id
        Route::match(['get', 'post'], 'citizens/applied-applications', 'getAllAppliedApplications');           // Get Applied Applications
        Route::post('citizens/independent-comment', 'commentIndependent');                                     // Independent Comment for the Citizen to be Tracked
        Route::get('citizens/get-transactions', 'getTransactionHistory');                                      // Get User Transaction History
        Route::post('change-citizen-pass', 'changeCitizenPass');                                               // Change the Password of The Citizen Using its Old Password 
    });

    /**
     * -----------------------------------------------------------------------------------------------
     * | Created By - Anshu Kumar
     * | Advertisement Module
     * -----------------------------------------------------------------------------------------------
     */

    // Self Advertisement
    Route::controller(SelfAdvertisementController::class)->group(function () {
        Route::post('crud/store-selfadvertisement', 'storeSelfAdvertisement');                          // Save Self Advertisement
        Route::get('crud/get-all-selfadvertisements-inbox', 'getAllSelfAdvertisementsInbox');           // Get All Self Advertisement Datas in Inbox
        Route::get('crud/get-all-selfadvertisements-outbox', 'getAllSelfAdvertisementsOutbox');         // Get All Self Advertisement Datas in Outbox
        Route::get('crud/get-selfadvertisement-by-id/{id}', 'getSelfAdvertisementByID');                // Get Self Advertisement By Id
        Route::put('crud/update-selfadvertisement/{id}', 'updateSelfAdvertisement');                    // Update Self Advertisement
        Route::delete('crud/del-selfadvertisement/{id}', 'deleteSelfAdvertisement');                    // Delete Self Advertisement By ID
    });

    /**
     * | Created On-19-08-2022 
     * | Created by-Anshu Kumar
     * | Ulb Wards operations
     */
    Route::controller(WardController::class)->group(function () {
        Route::post('store-ulb-wards', 'storeUlbWard');          // Save Ulb Ward
        Route::put('edit-ulb-ward/{id}', 'editUlbWard');         // Edit Ulb Ward
        Route::get('get-ulb-ward/{id}', 'getUlbWardByID');       // Get Ulb Ward Details by ID
        Route::get('get-all-ulb-wards', 'getAllUlbWards'); //not for use      // Get All Ulb Wards
        Route::post('get-newward-by-oldward', 'getNewWardByOldWard');
    });
});


// Routes used where authentication not required
Route::group(['middleware' => ['json.response', 'request_logger']], function () {
});


//==============================================================================================
//========================                WORKFLOW MASTER           ============================ 
//==============================================================================================


// /**
//  * Creation Date: 06-10-2022
//  * Created By:-   Mrinal Kumar
//  * Modified On :- 17-12-2022
//  * Modified By :- Mrinal Kumar
//  */

Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {


    /**
     * workflow roles CRUD operation
     */
    Route::controller(WorkflowRoleController::class)->group(function () {
        Route::post('crud/roles/save-role', 'create');                      // Save Role
        Route::put('crud/roles/edit-role', 'editRole');                     // edit Role
        Route::post('crud/roles/get-role', 'getRole');                      // Get Role By Id
        Route::get('crud/roles/get-all-roles', 'getAllRoles');              // Get All Roles
        Route::delete('crud/roles/delete-role', 'deleteRole');              // Delete Role
    });


    /**
     * ============== To be replaced with upper api  =================
     */
    Route::controller(RoleController::class)->group(function () {
        Route::post('workflow/roles/save', 'createRole');                   // Save Role
        Route::post('workflow/roles/edit', 'editRole');                     // edit Role
        Route::post('workflow/roles/get', 'getRole');                       // Get Role By Id
        Route::post('workflow/roles/list', 'getAllRoles');                  //Get All Roles          
        Route::post('workflow/roles/delete', 'deleteRole');                 // Delete Role
    });
    /**
     * ===================================================================
     */


    /**
     * Role User Map CRUD operation
     */

    Route::apiResource("roleusermap", WorkflowRoleUserMapController::class);


    /**
     * | Created On-14-12-2022 
     * | Created By-Mrinal Kumar
     * | Workflow Traits
     */
    Route::controller(WcController::class)->group(function () {
        Route::post('workflow-current-user', 'workflowCurrentUser');
        Route::post('workflow-initiator', 'workflowInitiatorData');
        Route::post('role-by-user', 'roleIdByUserId');
        Route::post('ward-by-user', 'wardByUserId');
        Route::post('role-by-workflow', 'getRole');
        Route::post('initiator', 'initiatorId');
        Route::post('finisher', 'finisherId');
    });

    /**
     * | for custom details
       | Serial No : 09
     */
    Route::controller(CustomController::class)->group(function () {
        Route::post('get-all-custom-tab-data', 'getCustomDetails');
        Route::post('post-custom-data', 'postCustomDetails');
    });
});

Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {

    /**
     * | Created On-23-11-2022 
     * | Created By-Sam kerketta
     * | Menu Permissions
     */
    Route::controller(MenuController::class)->group(function () {
        Route::get('crud/menu/get-all-menues', 'getAllMenues');                     // Get All the Menu List
        Route::post('crud/menu/delete-menues', 'deleteMenuesDetails');              // Soft Delition of the menus
        Route::post('crud/menu/add-new-menues', 'addNewMenues');                    // adding the details of the menues in the menue table
        Route::post('crud/menu/update-menues', 'updateMenuMaster');                 // Update the menu master 

        Route::post('menu/get-menu-by-id', 'getMenuById');                          // Get menu bu menu Id

        Route::post('menu-roles/get-menu-by-roles', 'getMenuByroles');              // Get all the menu by roles
        Route::post('menu-roles/update-menu-by-role', 'updateMenuByRole');          // Update Menu Permission By Role
        Route::post('menu-roles/list-parent-serial', 'listParentSerial');           // Get the list of parent menues

        Route::post('sub-menu/tree-structure', 'getTreeStructureMenu');             // Generation of the menu tree Structure        
        Route::post('sub-menu/get-children-node', 'getChildrenNode');               // Get the children menues

    });
});

/**
 * This Route is for Demo Purpose
 */
Route::controller(DemoController::class)->group(function () {
    Route::post('water-connection', 'waterConnection');
});

#---------------------------- document read ------------------------------
Route::get('/getImageLink', function () {
    return view('getImageLink');
});
