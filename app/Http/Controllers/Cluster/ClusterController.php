<?php

namespace App\Http\Controllers\Cluster;

use App\Http\Controllers\Controller;
use App\Models\Cluster\Cluster;
use App\Repository\Cluster\Interfaces\iCluster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClusterController extends Controller
{
    //constructer 
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
    public function getClusterById(Request $request)
    {
        return $this->cluster->getClusterById($request);
    }

    //updating the cluster details to the respective id
    public function editClusterDetails(Request $request)
    {
        # validation
        $validateUser = Validator::make(
            $request->all(),
            [
                'clusterName'   => 'required',
                'clusterType' => 'required',
                'id' => 'required',
                'clusterAddress' => 'required',
                'clusterMobileNo' => ['required', 'min:10', 'max:10'],
                'clusterAuthPersonName' => 'required'
            ]
        );
        if ($validateUser->fails()) {
            return $this->failure($validateUser->errors());
        }
        $cluster = new Cluster();
        return $cluster->editClusterDetails($request);
    }


    //saving the cluster details 
    public function saveClusterDetails(Request $request)
    {
        # validation
        $validateUser = Validator::make(
            $request->all(),
            [
                'clusterName'   => 'required',
                'clusterType' => 'required',
                'clusterAddress' => 'required',
                'clusterAuthPersonName' => 'required',
                'clusterMobileNo' => ['required', 'min:10', 'max:10']
            ]
        );
        if ($validateUser->fails()) {
            return $this->failure($validateUser->errors());
        }
        $cluster = new Cluster();
        return $cluster->saveClusterDetails($request);
    }

    //soft deletion of the cluster details 
    public function deleteClusterData(Request $request)
    {
        return $this->cluster->deleteClusterData($request);
    }

    // selecting details according to holding no
    public function detailsByHolding(Request $request)
    {
        # validation
        $validateUser = Validator::make(
            $request->all(),
            [
                'holdingNo'   => 'required',
            ]
        );
        if ($validateUser->fails()) {
            return $this->failure($validateUser->errors());
        }
        return $this->cluster->detailsByHolding($request);
    }

    // selecting details according to clusterID
    public function holdingByCluster(Request $request)
    {
        # validation
        $validateUser = Validator::make(
            $request->all(),
            [
                'clusterId'   => 'required|integer',
            ]
        );
        if ($validateUser->fails()) {
            return $this->failure($validateUser->errors());
        }
        return $this->cluster->holdingByCluster($request);
    }


    // selecting details according to clusterID
    public function saveHoldingInCluster(Request $request)
    {
        # validation
        $validateUser = Validator::make(
            $request->all(),
            [
                'clusterId'   => 'required|integer',
            ]
        );
        if ($validateUser->fails()) {
            return $this->failure($validateUser->errors());
        }
        return $this->cluster->saveHoldingInCluster($request);
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
