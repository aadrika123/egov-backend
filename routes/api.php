<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ApiMasterController;
use App\Http\Controllers\RoleController;

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
 * 
 * ***Code Test***
 * Code Tested By-Anil Mishra Sir
 * Code Testing Date:-25-06-2022
 */

// Route Used for Login and Register the User
Route::controller(UserController::class)->group(function () {
    Route::post('register', 'store');
    Route::post('login', 'loginAuth');
});

/**
 * Routes for API masters, api searchable and api edit
 * CreatedOn-29-06-2022
 */

Route::controller(ApiMasterController::class)->group(function () {
    Route::post('save-api', 'store');
    Route::post('edit-api/{id}', 'update');
    Route::post('search-api', 'search');
});

// Inside Middleware Routes with API Authenticate 
Route::group(['middleware' => ['cors', 'json.response', 'auth:sanctum']], function () {
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
    });

    /**
     * Route for Roles
     * Created By-Anshu Kumar
     * Created Date-27-06-2022
     * Route are authorized for super admin only using Middleware
     */
    Route::controller(RoleController::class)->group(function () {
        Route::group(['middleware' => ['can:isSuperAdmin']], function () {
            Route::post('save-role', 'storeRole');
            Route::post('edit-role/{id}', 'editRole');

            Route::post('role-menu', 'roleMenu');
            Route::post('edit-role-menu/{id}', 'editRoleMenu');

            Route::post('role-user', 'roleUser');
            Route::post('edit-role-user/{id}', 'editRoleUser');

            Route::post('role-menu-logs', 'roleMenuLogs');
            Route::post('edit-role-menu-logs/{id}', 'editRoleMenuLogs');

            Route::post('role-user-logs', 'roleUserLogs');
            Route::post('edit-role-user-logs/{id}', 'editRoleUserLogs');
        });
    });
});
