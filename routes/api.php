<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ApiMasterController;
use App\Http\Controllers\CitizenController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\Menupermission\MenuGroupsController;
use App\Http\Controllers\Menupermission\MenuItemsController;
use App\Http\Controllers\Menupermission\MenuMapController;
use App\Http\Controllers\Menupermission\MenuRolesController;
use App\Http\Controllers\Menupermission\MenuUlbrolesController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SelfAdvertisementController;
use App\Http\Controllers\UlbController;
use App\Http\Controllers\UlbWorkflowController;
use App\Http\Controllers\Workflows\WorkflowController;
use App\Http\Controllers\Workflows\WorkflowTrackController;
use App\Http\Controllers\PaymentMasterController;
use App\Http\Controllers\Trade\ApplyApplication;
use App\Http\Controllers\Ward\WardController;
use App\Http\Controllers\Ward\WardUserController;
use App\Http\Controllers\Workflows\UlbWorkflowRolesController;
use App\Http\Controllers\WorkflowMaster\WorkflowMasterController;
use App\Http\Controllers\WorkflowMaster\WfWorkflowController;
use App\Http\Controllers\WorkflowMaster\WorkflowMap;
use App\Http\Controllers\WorkflowMaster\WorkflowRoleController;
use App\Http\Controllers\WorkflowMaster\WorkflowWardUserController;
use App\Http\Controllers\WorkflowMaster\WorkflowRoleUserMapController;
use App\Http\Controllers\WorkflowMaster\WorkflowRoleMapController;


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

/**
 * | Created On-147-08-2022 
 * | Created By-Anshu Kumar
 * | Get all Ulbs by Ulb ID
 */
Route::controller(UlbController::class)->group(function () {
    Route::get('get-all-ulb', 'getAllUlb');
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

        // User Profile APIs
        Route::get('my-profile-details', 'myProfileDetails');   // For get My profile Details
        Route::put('edit-my-profile', 'editMyProfile');          // For Edit My profile Details

        Route::put('edit-user/{id}', 'update');
        Route::delete('delete-user', 'deleteUser');
        Route::get('get-user/{id}', 'getUser');
        Route::get('get-all-users', 'getAllUsers');

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
        // Route::group(['middleware' => ['can:isSuperAdmin']], function () {
        Route::post('save-role', 'storeRole');                      // Save Role
        Route::put('edit-role/{id}', 'editRole');                   // edit Role 
        Route::get('get-role/{id}', 'getRole');                     // Get Role By Id
        Route::get('get-all-roles', 'getAllRoles');                 // Get All Roles
        Route::delete('delete-role/{id}', 'deleteRole');            // Delete Role
        Route::get('master/roles/ulb-roles', 'getRoleListByUlb');   // Get All Roles by UlbID

        Route::post('role-menu', 'roleMenu');
        Route::put('edit-role-menu/{id}', 'editRoleMenu');
        Route::get('get-role-menu/{id}', 'getRoleMenu');
        Route::get('get-all-role-menus', 'getAllRoleMenus');

        Route::post('role-user', 'roleUser');                                   // Save user roles
        Route::put('edit-role-user', 'editRoleUser');                           // edit user roles 
        Route::get('get-role-user/{id}', 'getRoleUser');                        // get role user by id   
        Route::get('get-all-role-users', 'getAllRoleUsers');                    // get all role users
        Route::get('master/users/user-by-role-id/{id}', 'getUserByRoleID');     // Get Users List By Role ID

        Route::post('role-menu-logs', 'roleMenuLogs');
        Route::put('edit-role-menu-logs/{id}', 'editRoleMenuLogs');
        Route::get('get-role-menu-logs/{id}', 'getRoleMenuLogs');
        Route::get('get-all-role-menu-logs', 'getAllRoleMenuLogs');

        Route::post('role-user-logs', 'roleUserLogs');
        Route::put('edit-role-user-logs/{id}', 'editRoleUserLogs');
        Route::get('get-role-user-logs/{id}', 'getRoleUserLogs');
        Route::get('get-all-role-user-logs', 'getAllRoleUserLogs');
        // });
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
        Route::post('save-workflow-track', 'store');                                       // Save Workflow Track Messages
        Route::get('get-workflow-track/{id}', 'getWorkflowTrackByID');                     // Get Workflow Track Message By TrackID
        Route::get('gen/workflow-track/{RefTableID}/{RefTableValue}', 'getWorkflowTrackByTableIDValue');                     // Get WorkflowTrack By TableRefID and RefTableValue

        //changes by mrinal
        Route::get('workflow-track/getNotificationByCitizenId', 'getNotificationByCitizenId');
    });

    // Citizen Register
    Route::controller(CitizenController::class)->group(function () {
        Route::get('get-citizen-by-id/{id}', 'getCitizenByID');                                                // Get Citizen By ID
        Route::get('get-all-citizens', 'getAllCitizens');                                                      // Get All Citizens
        Route::post('edit-citizen-by-id/{id}', 'editCitizenByID');                                             // Approve Or Reject Citizen by Id
        Route::match(['get', 'post'], 'citizens/applied-applications', 'getAllAppliedApplications');           // Get Applied Applications
        Route::post('citizens/independent-comment', 'commentIndependent');                                      // Independent Comment for the Citizen to be Tracked
    });

    /**
     * -----------------------------------------------------------------------------------------------
     * | Created By - Anshu Kumar
     * | Advertisement Module
     * -----------------------------------------------------------------------------------------------
     */

    // Self Advertisement
    Route::controller(SelfAdvertisementController::class)->group(function () {
        Route::post('crud/store-selfadvertisement', 'storeSelfAdvertisement');      // Save Self Advertisement
        Route::get('crud/get-all-selfadvertisements-inbox', 'getAllSelfAdvertisementsInbox');         // Get All Self Advertisement Datas in Inbox
        Route::get('crud/get-all-selfadvertisements-outbox', 'getAllSelfAdvertisementsOutbox');         // Get All Self Advertisement Datas in Outbox
        Route::get('crud/get-selfadvertisement-by-id/{id}', 'getSelfAdvertisementByID');   // Get Self Advertisement By Id
        Route::put('crud/update-selfadvertisement/{id}', 'updateSelfAdvertisement');       // Update Self Advertisement
        Route::delete('crud/del-selfadvertisement/{id}', 'deleteSelfAdvertisement');       // Delete Self Advertisement By ID
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
        Route::get('get-all-ulb-wards', 'getAllUlbWards');       // Get All Ulb Wards
    });

    /**
     * | Created On-20-08-2022 
     * | Created By-Anshu Kumar
     * | Ward Users Masters Operations
     */
    Route::resource('ward/masters/ward-user', WardUserController::class);
});

// Routes used where authentication not required
Route::group(['middleware' => ['json.response', 'request_logger']], function () {
});


//==============================================================================================
//========================                WORKFLOW MASTER           ============================ 
//==============================================================================================


/**
 * Creation Date:-06-10-2022
 * Created By:- Mrinal Kumar
 */

Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {

    /**
     * workflow Master CRUD operation
     */
    Route::apiResource("master", WorkflowMasterController::class);


    /**
     * workflow workflow CRUD operation
     */
    Route::apiResource("workflow", WfWorkflowController::class);


    /**
     * workflow roles CRUD operation
     */
    Route::apiResource("role", WorkflowRoleController::class);

    /**
     * Ward User CRUD operation
     */
    Route::apiResource("warduser", WorkflowWardUserController::class);


    /**
     * Role User Map CRUD operation
     */

    Route::apiResource("roleusermap", WorkflowRoleUserMapController::class);



    /**
     * Workflow Role Map CRUD operation
     */

    Route::apiResource("rolemap", WorkflowRoleMapController::class);



    /**
     * Workflow Mapping CRUD operation
     */

    Route::controller(WorkflowMap::class)->group(function () {

        //Mapping
        Route::post('workflows/getroledetails', 'getRoleDetails');
        Route::post('workflow/getUserById', 'getUserById');
        Route::post('workflow/getWorkflowNameByUlb', 'getWorkflowNameByUlb');
        Route::post('workflow/getRoleByUlb', 'getRoleByUlb');
        Route::post('workflow/getWardByUlb', 'getWardByUlb');
        Route::post('workflow/getRoleByWorkflowId', 'getRoleByWorkflowId');
        Route::post('workflow/getUserByRole', 'getUserByRole');

        //mapping
        Route::post('workflow/getRoleByWorkflow', 'getRoleByWorkflow');
        Route::post('workflow/getUserByWorkflow', 'getUserByWorkflow');
        Route::post('workflow/getWardsInWorkflow', 'getWardsInWorkflow');
        Route::post('workflow/getUlbInWorkflow', 'getUlbInWorkflow'); //
        Route::post('workflow/getWorkflowByRole', 'getWorkflowByRole');
        Route::post('workflow/getUserByRoleId', 'getUserByRoleId');
        Route::post('workflow/getWardByRole', 'getWardByRole');
        Route::post('workflow/getUlbByRole', 'getUlbByRole');
        Route::post('workflow/getUserInUlb', 'getUserInUlb');
        Route::post('workflow/getRoleInUlb', 'getRoleInUlb');
        Route::post('workflow/getWorkflowInUlb', 'getWorkflowInUlb');

        Route::post('workflow/getRoleByUserUlbId', 'getRoleByUserUlbId');
        Route::post('workflow/getRoleByWardUlbId', 'getRoleByWardUlbId');

        Route::post('workflow/getWorkflownameByWorkflow', 'getWorkflownameByWorkflow');
    });




    /**
     * Workflow Track CRUD operation
     */

    // Route::controller(WorkflowTrackControllers::class)->group(function () {

    //     Route::post('workflow/track-create', 'create');                            // create data
    //     Route::get('workflow/track-list', 'list');                                 // list all data 
    //     Route::delete('workflow/track-delete/{id}', 'delete');                     // Delete data
    //     Route::put('workflow/track-update/{id}', 'update');                        // update data 
    //     Route::get('workflow/track-view/{id}', 'view');                            // Get data By Id

    // });
});



/**
 * ----------------------------------------------------------------------------------------
 * |                    Menu Permission Module Routes
 * ----------------------------------------------------------------------------------------
 * Created on- 14-10-2022
 * Created By- sam Kerketta
 *  
 */
Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {

    /**
     * Track CRUD operation for the Menu_Ulbroles
     */
    Route::controller(MenuUlbrolesController::class)->group(function () {
        Route::get('menuPermission/getMenuUlbroles', 'getMenuUlbroles');                    // get all data
        Route::post('menuPermission/addMenuUlbroles', 'addMenuUlbroles');                   // create data
        Route::put('menuPermission/updateMenuUlbroles/{id}', 'updateMenuUlbroles');         // update data by Id
        Route::delete('menuPermission/deleteMenuUlbroles/{id}', 'deleteMenuUlbroles');      // delete data by Id
    });

    /**
     * Track CRUD operation for the Menu_Roles
     */
    Route::controller(MenuRolesController::class)->group(function () {
        Route::get('menuPermission/getMenuRoles', 'getMenuRoles');                          // get all data
        Route::post('menuPermission/addMenuRoles', 'addMenuRoles');                         // create data
        Route::put('menuPermission/updateMenuRoles/{id}', 'updateMenuRoles');               // update data by Id
        Route::delete('menuPermission/deleteMenuRoles/{id}', 'deleteMenuRoles');            // delete data by Id
    });

    /**
     * Track CRUD operation for the Menu_Maps
     */
    Route::controller(MenuMapController::class)->group(function () {
        Route::get('menuPermission/getMenuMap/{id}', 'getMenuMap');                         // get all data by Id
        Route::post('menuPermission/addMenuMap', 'addMenuMap');                             // create data
        Route::put('menuPermission/updateMenuMap/{id}', 'updateMenuMap');                   // update data by Id
        Route::delete('menuPermission/deleteMenuMap/{id}', 'deleteMenuMap');                // delete data by Id
    });

    /**
     * Track CRUD operation for the Menu_Items
     */
    Route::controller(MenuItemsController::class)->group(function () {
        Route::get('menuPermission/getMenuItems', 'getMenuItems');                          // get all data
        Route::post('menuPermission/addMenuItems', 'addMenuItems');                         // create data
        Route::put('menuPermission/updateMenuItems/{id}', 'updateMenuItems');               // update data by Id
        Route::delete('menuPermission/deleteMenuItems/{id}', 'deleteMenuItems');            // delete data by Id
        /*
        * ----------------------------------------------------------------------------------------
        * Route::post('MenuPermission/MenuRoles', 'MenuRoles');                             // test Api
        * Route::post('MenuPermission/MenuGroups', 'MenuGroup');                            // test Api
        * ----------------------------------------------------------------------------------------
        */
    });

    /**
     * Track CRUD operation for the Menu_Groups
     * using Autherization for Psudo
     */
    Route::controller(MenuGroupsController::class)->group(function () {
        Route::group(['middleware' => 'can:isAdmin'], function () {
            Route::get('menuPermission/getAllMenuGroups', 'getAllMenuGroups');              // get all data
            Route::post('menuPermission/addMenuGroups', 'addMenuGroups');                   // create data
            Route::put('menuPermission/updateMenuGroups/{id}', 'updateMenuGroups');         // update data by Id
            Route::delete('menuPermission/deleteMenuGroups/{id}', 'deleteMenuGroups');      // delete data by Id
        });
    });
});

/**
 * Track the GET DATA operation for the Menu_Group,Menu_roles
 * using Autherization for Employ as 
 */
// Route::group(['middleware' => ['auth:sanctum',]], function () {
// secure routes for the Admin
Route::controller(MenuItemsController::class)->group(function () {
    // Route::group(['middleware' => 'can:isAdmin'], function () {
    Route::post('menu-Permission/get-Menu-Groups', 'menuGroupWiseItems');                       // get all MenuGroups                  
    Route::post('menu-Permission/get-Roles', 'ulbWiseMenuRole');                                // get all MenuRoles
    Route::post('menu-Permission/get-Menu-Roles-Items', 'menuGroupAndRoleWiseItems');
    Route::put('menu-Permission/put-Menu-Maps-Items', 'uplodeDataInMenuMaps');           // get role wise items
    // });
});
// });
