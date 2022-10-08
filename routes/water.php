<?php

use App\Http\Controllers\Water\NewConnectionController;
use Illuminate\Support\Facades\Route;

/**
 * | ----------------------------------------------------------------------------------
 * | Water Module Routes |
 * |-----------------------------------------------------------------------------------
 * | Created On-06-10-2022 
 * | Created For-The Routes defined for the Water Usage Charge Management System Module
 * | Created By-Anshu Kumar
 */

Route::post('/apply-new-connection', function () {
    dd('Welcome to simple Water route file');
});

Route::group(['middleware'=>['json.response','request_logger']],function(){
    /**
     * | Created On-07-10-2022 
     * | Created by-Anshu Kumar
     * | ------------------- Apply New Water Connection ------------------------ |
     */
    Route::resource('crud/new-connection',NewConnectionController::class);
});