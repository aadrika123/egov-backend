<?php

namespace App\Repository\Cluster\Interfaces;

use Illuminate\Http\Request;


interface iCluster
{
    public function getAllClusters();
    public function getClusterById($request);
    public function editClusterDetails($request);
    public function saveClusterDetails($request);
    public function deleteClusterData($request);
}
