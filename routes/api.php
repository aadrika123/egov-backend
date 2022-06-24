<?php

use Illuminate\Http\Request;
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

// ROUTES USED FOR LOGIN AND REGISTER ONLY
Route::controller(UserController::class)->group(function () {
    Route::post('register', 'store');
    Route::post('login', 'loginAuth');
});

// INSIDE MIDDLEWARE ROUTES WITH API AUTHENTICATE ETC.
Route::group(['middleware' => ['cors', 'json.response', 'api.key', 'auth:sanctum']], function () {
    // ROUTES FOR USER CONTROLLERS
    Route::controller(UserController::class)->group(function () {
        Route::post('test', 'testing');
        Route::post('logout', 'logOut');
        Route::post('change-password', 'changePass');
    });
});
