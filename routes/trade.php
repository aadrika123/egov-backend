<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Trade\ApplyApplication;

/**
 * | Created On-06-10-2022 
 * | Created For-The Routes defined for the Water Usage Charge Management System Module
 * | Created By-SandeepBara
 */

Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {
    /**
     *  -----------------------------------------------------------------
     * |                TRADE MODULE                                      |
     *  ------------------------------------------------------------------  
     * Created on- 06-10-2022
     * Created By- Sandeep Bara
     *  
     */
    Route::controller(ApplyApplication::class)->group(function () {        
        Route::match(["get", "post"], 'apply/{applicationType}', 'applyApplication');
        Route::post('getCharge', 'paybleAmount');
        Route::post('getPropertyByHolding', 'validate_holding_no');
        Route::post('updateBasicDtl', 'updateBasicDtl');
        Route::get('getLicenceDtl/{id}', 'getLicenceDtl');
        Route::post('searchLicence', 'searchLicence');
    });
});
Route::controller(ApplyApplication::class)->group(function () {    
    Route::get('paymentRecipt/{id}/{transectionId}', 'paymentRecipt');
});