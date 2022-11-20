<?php

namespace App\Http\Controllers\Cluster;

use App\Http\Controllers\Controller;
use App\Repository\Cluster\Interfaces\iCluster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClusterController extends Controller
{
    //
    private iCluster $cluster;
    public function __construct(iCluster $cluster)
    {
        $this->cluster = $cluster;
    }

    // get all list of the cluster
    public function getAllClusters()
    {
        return $this->cluster->getAllClusters();
    }

    //get all details of the cluster accordin to the id
    public function getClusterById($id)
    {
        return $this->cluster->getClusterById($id);
    }

    //updating the cluster details to the respective id
    public function editClusterDetails(Request $request)
    {
        # validation
        $validateUser = Validator::make(
            $request->all(),
            [
                'ulbId'   => 'required|integer',
                'userId'   => 'required|integer',
                'clusterName'   => 'required',
                'clusterType' => 'requred',
                'id' => 'required'
            ]
        );
        if ($validateUser->fails()) {
            return $this->failure($validateUser->errors());
        }
        return $this->cluster->editClusterDetails($request);
    }


    //saving the cluster details 
    public function saveClusterDetails(Request $request)
    {
        # validation
        $validateUser = Validator::make(
            $request->all(),
            [
                'ulbId'   => 'required|integer',
                'userId'   => 'required|integer',
                'clusterName'   => 'required',
                'clusterType' => 'requred',
            ]
        );
        if ($validateUser->fails()) {
            return $this->failure($validateUser->errors());
        }
        return $this->cluster->editClusterDetails($request);
    }

    //soft deletion of the cluster details 
    public function deleteClusterData($id)
    {
        return $this->cluster->deleteClusterData($id);
    }

    /**
     * | ----------------- Common funtion for the return component in failer ------------------------------- |
     * | @param req
     * | @var mreturn
     * | Operation : returning the messge using (responseMsg)
     */
    public function failure($req)
    {
        $return = responseMsg(false, "Validation error!", $req);
        return (object)$return;
    }
}
