<?php


use App\Http\Controllers\Cluster\ClusterController;
use Illuminate\Support\Facades\Route;

// Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {
    Route::controller(ClusterController::class)->group(function () {
        Route::get('get-all-clusters', 'getAllClusters');
        Route::post('get-cluster-by-id/{id}', 'getClusterById');
        Route::put('edit-cluster-details', 'editClusterDetails');
        Route::post('save-cluster-details', 'saveClusterDetails');
        Route::delete('delete-cluster-data/{id}', 'deleteClusterData');
    });
// });
