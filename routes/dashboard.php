<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\JskController;



/**
 * Creation Date: 21-03-2023
 * Created By  :- Mrinal Kumar
 */

Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {

    /**
     * | Workflow Document Controller
         Serial No : 01
     */
    Route::controller(JskController::class)->group(function () {
        Route::post('jsk/prop-details', 'propDtl');               // 01
        Route::post('jsk/prop-dashboard', 'jskPropDashboard');
    });
});
