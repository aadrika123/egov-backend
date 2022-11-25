<?php

namespace App\Repository\Cluster\Interfaces;

use Illuminate\Http\Request;


interface iCluster
{
    public function getAllClusters();
    public function getClusterById($request);
    public function deleteClusterData($request);

    # cluster maping
    public function detailsByHolding($request); //<---------- remark 
    public function holdingByCluster($request);
    public function saveHoldingInCluster($request);
}
