<?php

use App\Http\Controllers\Grievance\GrievaceController;
use Illuminate\Support\Facades\Route;


// Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {

# Grievamne API list basic 
Route::controller(GrievaceController::class)->group(function () {
    Route::post('postFileComplaint', 'postFileComplain');
    Route::get('getAllComplainById/{id}', 'getAllComplainById');
});
// });