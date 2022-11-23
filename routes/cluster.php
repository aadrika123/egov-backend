<?php


use App\Http\Controllers\Cluster\ClusterController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {
    Route::controller(ClusterController::class)->group(function () {
        #cluster data entry
        Route::get('get-all-clusters', 'getAllClusters');
        Route::post('get-cluster-by-id', 'getClusterById');
        Route::post('edit-cluster-details', 'editClusterDetails');
        Route::post('save-cluster-details', 'saveClusterDetails');
        Route::delete('delete-cluster-data', 'deleteClusterData');

        # cluster maping
        Route::post('details-by-holding', 'detailsByHolding');
        Route::post('holding-by-cluster', 'holdingByCluster');
        Route::post('save-holding-in-cluster', 'saveHoldingInCluster');
    });
});
