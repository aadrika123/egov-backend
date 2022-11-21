<?php

namespace App\Repository\Cluster\Interfaces;

use Illuminate\Http\Request;


interface iCluster
{
    public function getAllClusters();
    public function getClusterById($id);
    public function editClusterDetails($request);
    public function saveClusterDetails($request);
    public function deleteClusterData($id);
}