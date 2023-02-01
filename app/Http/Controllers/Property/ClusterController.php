<?php

namespace App\Http\Controllers\property;

use App\Http\Controllers\Controller;
use App\Models\Cluster\Cluster;
use App\Repository\Cluster\Interfaces\iCluster;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * | Property Cluster
 * | Created By - Sam kerketta
 * | Created On- 23-11-2022 
 * | Cluster Related All Operations Are Listed Below.
 */

class ClusterController extends Controller
{
    /**
     * |----------------------------- constructer -------------------------------|
     */
    private iCluster $cluster;
    public function __construct(iCluster $cluster)
    {
        $this->cluster = $cluster;
    }

    // get all list of the cluster
    public function getAllClusters()
    {
        try {
            $obj = new Cluster();
            $clusterList = $obj->allClusters();
            return responseMsgs(true, "Fetched all Cluster!", $clusterList, "", "02", "320.ms", "GET", "");
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    // get all details of the cluster accordin to the id
    public function getClusterById(Request $request)
    {
        try {
            return $this->cluster->getClusterById($request);
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    //updating the cluster details to the respective id
    public function editClusterDetails(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'clusterName'           => 'required',
                    'clusterType'           => 'required',
                    'id'                    => 'required',
                    'clusterAddress'        => 'required',
                    'clusterMobileNo'       => ['required', 'min:10', 'max:10'],
                    'clusterAuthPersonName' => 'required'
                ]
            );
            if ($validateUser->fails()) {
                return $this->validation($validateUser->errors());
            }
            $cluster = new Cluster();
            $cluster->editClusterDetails($request);
            return responseMsgs(true, "Cluster Edited By Id!", "", "", "02", "320.ms", "POST", "");
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    //saving the cluster details 
    public function saveClusterDetails(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'clusterName'           => 'required',
                    'clusterType'           => 'required',
                    'clusterAddress'        => 'required',
                    'clusterAuthPersonName' => 'required',
                    'clusterMobileNo'       => ['required', 'min:10', 'max:10']
                ]
            );
            if ($validateUser->fails()) {
                return $this->validation($validateUser->errors());
            }
            $obj = new Cluster();
            $obj->saveClusterDetails($request);
            return responseMsgs(true, "Data Saved!", "", "", "02", "", "POST", "");
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    //soft deletion of the cluster details 
    public function deleteClusterData(Request $request)
    {
        try {
            $obj = new Cluster();
            $obj->deleteClusterData($request);
            return responseMsgs(true, "Cluster Deleted!", "", "", "02", "", "POST", "");
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    // selecting details according to holding no
    public function detailsByHolding(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'holdingNo'   => 'required',
                ]
            );
            if ($validateUser->fails()) {
                return $this->validation($validateUser->errors());
            }
            return $this->cluster->detailsByHolding($request->holdingNo);
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    /**
     * |----------------------------------- Cluster Maping ----------------------------------------|
     * | Date : 24-11-22
     */

    // selecting details according to clusterID
    public function holdingByCluster(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'clusterId'   => 'required|integer',
                ]
            );
            if ($validateUser->fails()) {
                return $this->validation($validateUser->errors());
            }
            return $this->cluster->holdingByCluster($request);
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }


    // selecting details according to clusterID
    public function saveHoldingInCluster(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'clusterId'   => 'required|integer',
                ]
            );
            if ($validateUser->fails()) {
                return $this->validation($validateUser->errors());
            }
            return $this->cluster->saveHoldingInCluster($request);
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }







    /**
     * |----------------------------------- Common functions ----------------------------------------|
     * |date : 21-11-22
     */

    /**
     * | ----------------- Common funtion for the return component in failer ------------------------------- |
     * | @param req
     * | @var return
     * | Operation : returning the messge using (responseMsg)
     */
    public function validation($req)
    {
        $return = responseMsg(false, "Validation error!", $req);
        return (object)$return;
    }
}
