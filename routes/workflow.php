<?php

use App\Http\Controllers\PermissionController;
use App\Http\Controllers\WorkflowMaster\MasterController;
use App\Http\Controllers\WorkflowMaster\RoleController;
use App\Http\Controllers\WorkflowMaster\WardUserController;
use App\Http\Controllers\WorkflowMaster\WorkflowController as WfController;
use App\Http\Controllers\WorkflowMaster\WorkflowMap;
use App\Http\Controllers\WorkflowMaster\WorkflowRoleMapController;
use App\Http\Controllers\WorkflowMaster\WorkflowRoleUserMapController;
use App\Http\Controllers\Workflows\WfDocumentController;
use Illuminate\Support\Facades\Route;


/**
 * Creation Date: 06-10-2022
 * Created By  :- Mrinal Kumar
 * Modified On :- 17-12-2022
 * Modified By :- Mrinal Kumar
 */

Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger', 'expireBearerToken']], function () {

    /**
     * workflow Master CRUD operation
     */

    Route::controller(MasterController::class)->group(function () {
        Route::post('master/save', 'createMaster');                     #API_ID = 025811
        Route::post('master/edit', 'updateMaster');                     #API_ID = 025812 
        Route::post('master/byId', 'masterbyId');                       #API_ID = 025813
        Route::post('master/list', 'getAllMaster');                     #API_ID = 025814
        Route::post('master/delete', 'deleteMaster');                   #API_ID = 025815
    });


    /**
     * Wf workflow CRUD operation
     */

    Route::controller(WfController::class)->group(function () {
        Route::post('wfworkflow/save', 'createWorkflow');               #API_ID = 025821
        Route::post('wfworkflow/edit', 'updateWorkflow');               #API_ID = 025822 
        Route::post('wfworkflow/byId', 'workflowbyId');                 #API_ID = 025823
        Route::post('wfworkflow/list', 'getAllWorkflow');               #API_ID = 025824
        Route::post('wfworkflow/delete', 'deleteWorkflow');             #API_ID = 025825
    });


    /**
     * workflow roles CRUD operation
     */
    // Route::controller(WorkflowRoleController::class)->group(function () {
    //     Route::post('crud/roles/save-role', 'create');                      // Save Role
    //     Route::put('crud/roles/edit-role', 'editRole');                     // edit Role
    //     Route::post('crud/roles/get-role', 'getRole');                      // Get Role By Id
    //     Route::get('crud/roles/get-all-roles', 'getAllRoles');              // Get All Roles
    //     Route::delete('crud/roles/delete-role', 'deleteRole');              // Delete Role
    // });


    /**
     * ============== To be replaced with upper api  =================
     */
    Route::controller(RoleController::class)->group(function () {
        Route::post('roles/save', 'createRole');                        #API_ID = 025831
        Route::post('roles/edit', 'editRole');                          #API_ID = 025832
        Route::post('roles/get', 'getRole');                            #API_ID = 025833
        Route::post('roles/list', 'getAllRoles');                       #API_ID = 025834          
        Route::post('roles/delete', 'deleteRole');                      #API_ID = 025835
    });
    /**
     * ===================================================================
     */


    /**
     * Ward User CRUD operation
     */
    Route::controller(WardUserController::class)->group(function () {
        Route::post('ward-user/save', 'createWardUser');                #API_ID = 025841
        Route::post('ward-user/edit', 'updateWardUser');                #API_ID = 025842 
        Route::post('ward-user/byId', 'WardUserbyId');                  #API_ID = 025843
        Route::post('ward-user/list', 'getAllWardUser');                #API_ID = 025844
        Route::post('ward-user/delete', 'deleteWardUser');              #API_ID = 025845
        Route::post('ward-user/list-tc', 'tcList');                     #API_ID = 025846
    });


    /**
     * Role User Map CRUD operation
     */
    Route::controller(WorkflowRoleUserMapController::class)->group(function () {
        Route::post('role-user-maps/get-roles-by-id', 'getRolesByUserId');       #API_ID = 025856                 // Get Permitted Roles By User ID
        Route::post('role-user-maps/update-user-roles', 'updateUserRoles');      #API_ID = 025857                 // Enable or Disable User Role
    });


    /**
     * Workflow Role Map CRUD operation
     */

    Route::controller(WorkflowRoleMapController::class)->group(function () {
        Route::post('role-map/save', 'createRoleMap');                  #API_ID = 025861
        Route::post('role-map/edit', 'updateRoleMap');                  #API_ID = 025862 
        Route::post('role-map/byId', 'roleMapbyId');                    #API_ID = 025863
        Route::post('role-map/list', 'getAllRoleMap');                  #API_ID = 025864
        Route::post('role-map/delete', 'deleteRoleMap');                #API_ID = 025865
        Route::post('role-map/workflow-info', 'workflowInfo');          #API_ID = 025866
    });


    /**
     * Workflow Mapping CRUD operation
     */

    Route::controller(WorkflowMap::class)->group(function () {

        //Mapping
        Route::post('getroledetails', 'getRoleDetails');                #API_ID = 025871
        Route::post('getUserById', 'getUserById');                      #API_ID = 025872
        Route::post('getWorkflowNameByUlb', 'getWorkflowNameByUlb');    #API_ID = 025873
        Route::post('getRoleByUlb', 'getRoleByUlb');                    #API_ID = 025874
        Route::post('getWardByUlb', 'getWardByUlb');                    #API_ID = 025875 #same // auth
        Route::post('getUserByRole', 'getUserByRole');                  #API_ID = 025876

        //mapping
        Route::post('getRoleByWorkflow', 'getRoleByWorkflow');          #API_ID = 025877
        Route::post('getUserByWorkflow', 'getUserByWorkflow');          #API_ID = 025878
        Route::post('getWardsInWorkflow', 'getWardsInWorkflow');        #API_ID = 025879
        Route::post('getUlbInWorkflow', 'getUlbInWorkflow'); //         #API_ID = 025880
        Route::post('getWorkflowByRole', 'getWorkflowByRole');          #API_ID = 025881
        Route::post('getUserByRoleId', 'getUserByRoleId');              #API_ID = 025882
        Route::post('getWardByRole', 'getWardByRole');                  #API_ID = 025883
        Route::post('getUlbByRole', 'getUlbByRole');                    #API_ID = 025884
        Route::post('getUserInUlb', 'getUserInUlb');                    #API_ID = 025885
        Route::post('getRoleInUlb', 'getRoleInUlb');                    #API_ID = 025886
        Route::post('getWorkflowInUlb', 'getWorkflowInUlb');            #API_ID = 025887

        Route::post('getRoleByUserUlbId', 'getRoleByUserUlbId');        #API_ID = 025888
        Route::post('getRoleByWardUlbId', 'getRoleByWardUlbId');        #API_ID = 025889

        Route::post('get-ulb-workflow', 'getWorkflow');                 #API_ID = 025890
    });
});

// for unautheticated citizen
Route::controller(WorkflowMap::class)->group(function () {
    Route::post('wardByUlb', 'getWardByUlb');                           #API_ID = 025875  #same // unauth
});
