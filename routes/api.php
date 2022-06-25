<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

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

// Inside Middleware Routes with API Authenticate 
Route::group(['middleware' => ['cors', 'json.response', 'api.key', 'auth:sanctum']], function () {
    // Routes for User Controller
    Route::controller(UserController::class)->group(function () {
        Route::get('test', 'testing');
        Route::post('logout', 'logOut');
        Route::post('change-password', 'changePass');
    });
});
