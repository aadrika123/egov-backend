<?php

namespace App\Http\Controllers\property;

use App\Http\Controllers\Controller;
use App\Models\Cluster\Cluster;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropProperty;
use App\Repository\Cluster\Interfaces\iCluster;
use Exception;
use Illuminate\Database\Eloquent\Collection;
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

    /**
     * |----------------------------------- Cluster Maping ----------------------------------------|
     * | Date : 24-11-22
     */

    // selecting details according to holding no // Change the repositery
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
            $mPropProperty = new PropProperty();
            $holdingDetails = $mPropProperty->searchHolding($request->holdingNo)->get();
            return responseMsgs(true, "List of holding!", $holdingDetails, "", "02", "", "POST", "");
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }


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

            $mPropProperty = new PropProperty();
            $propDetails = $mPropProperty->searchPropByCluster($request->clusterId)->get();
            return responseMsgs(true, "List of holding Grouped By Cluster!", $propDetails, "", "02", "", "POST", "");
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }


    // selecting details according to clusterID
    public function saveHoldingInCluster(Request $request)
    {
        try {
            $request->validate([
                'clusterId'     => 'required|integer',
                'holdingNo'     => "required|array",
            ]);
            $uniqueValues = collect($request->holdingNo)->unique();
            if ($uniqueValues->count() !== count($request->holdingNo)) {
                return responseMsg(false, "holding no Contain Dublicate Value!", "");
            }
            $mPropProperty = new PropProperty();
            $results = $mPropProperty->searchCollectiveHolding($request->holdingNo);
            if ($results->count() !== count($request->holdingNo)) {
                return responseMsg(false, "the holding details contain invalid data", "");
            }

            $notActive = "Not a valid cluter ID!";
            $mCluster = new Cluster();
            $checkActiveCluster =  $mCluster->checkActiveCluster($request->clusterId);
            $verifyCluster = collect($checkActiveCluster)->first();
            if ($verifyCluster) {
                $holdingList = collect($request->holdingNo);
                PropProperty::whereIn('new_holding_no', $holdingList)
                    ->update([
                        'cluster_id' => $request->clusterId
                    ]);
                return responseMsgs(true, "Holding is Added to the respective Cluster!", $request->clusterId, "", "02", "", "POST", "");
            }
            return responseMsg(false, $notActive, "");
        } catch (Exception $error) {
            return responseMsg(false, $error->getMessage(), "");
        }
    }

    /**
     * | Search Saf by by Saf no
        | Route creation
     */
    public function getSafBySafNo(Request $request)
    {
        $request->validate([
            'safNo' => 'required',
        ]);
        try {
            $mPropActiveSaf = new PropActiveSaf();
            $application = collect($mPropActiveSaf->searchSafDtlsBySafNo($request->safNo));
            return responseMsgs(true, "Listed Saf!", $application, "", "02", "", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // selecting details according to clusterID // Route
    public function saveSafInCluster(Request $request)
    {
        try {
            $request->validate([
                'clusterId'     => 'required|integer',
                'safNo'     => "required|array",
            ]);
            $uniqueValues = collect($request->safNo)->unique();
            if ($uniqueValues->count() !== count($request->safNo)) {
                return responseMsg(false, "holding no Contain Dublicate Value!", "");
            }
            $mPropActiveSaf = new PropActiveSaf();
            $results = $mPropActiveSaf->searchCollectiveSaf($request->safNo);
            if ($results->count() !== count($request->safNo)) {
                return responseMsg(false, "the holding details contain invalid data", "");
            }

            $notActive = "Not a valid cluter ID!";
            $mCluster = new Cluster();
            $checkActiveCluster =  $mCluster->checkActiveCluster($request->clusterId);
            $verifyCluster = collect($checkActiveCluster)->first();
            if ($verifyCluster) {
                $safNoList = collect($request->safNo);
                PropActiveSaf::whereIn('saf_no', $safNoList)
                    ->update([
                        'cluster_id' => $request->clusterId
                    ]);
                return responseMsgs(true, "saf is Added to the respective Cluster!", $request->clusterId, "", "02", "", "POST", "");
            }
            return responseMsg(false, $notActive, "");
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
