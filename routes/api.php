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
Route::group(['middleware' => ['cors', 'json.response', 'api.key', 'auth:sanctum']], function () {
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
        Route::post('save-role', 'storeRole')->middleware('can:isSuperAdmin');
        Route::post('role-menu', 'roleMenu')->middleware('can:isSuperAdmin');
        Route::post('role-user', 'roleUser')->middleware('can:isSuperAdmin');
        Route::post('role-menu-logs', 'roleMenuLogs')->middleware('can:isSuperAdmin');
        Route::post('role-user-logs', 'roleUserLogs')->middleware('can:isSuperAdmin');
    });
});
