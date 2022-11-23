<?php

namespace App\Repository\Cluster\Concrete;

use App\Models\Cluster\Cluster;
use App\Models\Property\PropProperty;
use App\Repository\Cluster\Interfaces\iCluster;
use Exception;


class ClusterRepository implements iCluster
{

    /**
     * | ----------------- Collecting all data of the cluster/returning/master ------------------------------- |
     * | @var mdetails
     * | Operation : read table cluster and returning the data 
     */
    public function getAllClusters()
    {
        try {
            $mdetails = Cluster::select(
                'id',
                'cluster_name AS name',
                'cluster_type AS type',
                'address',
                'mobile_no AS mobileNo',
                'authorized_person_name AS authPersonName'
            )
                ->where('status', '1')
                ->get();
            return $this->success($mdetails);
        } catch (Exception $error) {
            return $this->failure($error->getMessage());
        }
    }

    /**
     * | ----------------- Collecting all data of the cluster according to id /returning/master ------------------------------- |
     * | @var mdetails
     * | Operation : read table cluster and returning the data according to id  
     */
    public function getClusterById($request)
    {

        try {
            $mdetails = Cluster::select(
                'cluster_name AS name',
                'cluster_type AS type',
                'address',
                'mobile_no AS mobileNo',
                'authorized_person_name AS authorizedPersonName',
                'status'
            )
                ->where('id', $request->id)
                ->get()
                ->first();

            if (!null == ($mdetails) && $mdetails->status == 1) {
                return $this->success($mdetails);
            }
            return  $this->noData();
        } catch (Exception $error) {
            return $this->failure($error->getMessage());
        }
    }


    /**
     * | ------------------------- updating the cluster data/master ------------------------------- |
     * | @param requestuest
     * | @var userId
     * | @var ulbId
     * | @param error
     * | Operation : updating the cluster data whith new data
     */
    public function editClusterDetails($request)
    {
        try {
            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;
            Cluster::where('id', $request->id)
                ->update([
                    'ulb_id' => $ulbId,
                    'user_id' => $userId,
                    'cluster_name' => $request->clusterName,
                    'cluster_type' => $request->clusterType,
                    'address' => $request->address,
                    'mobile_no' => $request->mobileNo,
                    'authorized_person_name' => $request->authorizedPersonName
                ]);
            return $this->success($request->id);
        } catch (Exception $error) {
            return $this->failure($error->getMessage());
        }
    }


    /**
     * | ----------------- saving the data of the cluster/master ------------------------------- |
     * | @param requestuest
     * | @param error
     * | @var userId
     * | @var ulbId
     * | @var newCluster
     * | Operation : saving the data of cluster 
     */
    public function saveClusterDetails($request)
    {
        try {
            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;

            $newCluster = new Cluster();
            $newCluster->ulb_id  = $ulbId;
            $newCluster->cluster_name  = $request->clusterName;
            $newCluster->cluster_type  = $request->clusterType;
            $newCluster->address  = $request->address;
            $newCluster->user_id  = $userId;
            $newCluster->mobile_no = $request->mobileNo;
            $newCluster->authorized_person_name = $request->authorizedPersonName;
            $saved = $newCluster->save();

            if ($saved) {
                return $this->success($request->userId);
            }
            return  $this->failure($request->userId);
        } catch (Exception $error) {
            return $this->failure($error->getMessage());
        }
    }

    /**
     * | ----------------- deleting the data of the cluster/master ------------------------------- |
     * | @param requestuest
     * | @param error
     * | @var userId
     * | @var ulbId
     * | @var newCluster
     * | Operation : soft delete of the respective detail 
     */
    public function deleteClusterData($request)
    {
        try {
            Cluster::where('id', $request->id)
                ->update(['status' => "0"]);
            return $this->success($request->id);
        } catch (Exception $error) {
            return  $this->failure($error->getMessage());
        }
    }


    /**
     * | ----------------- details of the respective holding NO ------------------------------- |
     * | @param request
     * | @var holdingCheck
     * | Operation : returning the messge using (responseMsg)
     */
    public function detailsByHolding($request)
    {
        try {
            $holdingCheck = PropProperty::join('prop_owners', 'prop_owners.saf_id', '=', 'prop_properties.saf_id')
                ->join('prop_properties', 'prop_properties.prop_type_mstr_id', '=', 'ref_prop_types.id')
                ->select(
                    'prop_properties.new_ward_mstr_id AS wardId',
                    'prop_owners.owner_name AS ownerName',
                    'prop_properties.prop_address AS address',
                    'ref_prop_types.property_type AS propertyType',
                    'prop_owners.mobile_no AS mobileNo'
                )
                ->where('prop_properties.new_holding_no', $request->holdingNo)
                ->where('prop_properties.status', '1')
                ->get();
            return $this->success($holdingCheck);
        } catch (Exception $error) {
            return $this->failure($error->getMessage());
        }
    }


    /**
     * | ----------------- respective holding according to cluster ID ------------------------------- |
     * | @param request
     * | @var clusterDetails
     * | Operation : 385ms
     * | rating - 2
     */
    public function holdingByCluster($request)
    {
        try {
            $clusterDetails = PropProperty::join('prop_owners', 'prop_owners.saf_id', '=', 'prop_properties.saf_id')
                ->join('ref_prop_types', 'ref_prop_types.id', '=', 'prop_properties.prop_type_mstr_id')
                ->select(
                    'prop_properties.new_ward_mstr_id AS wardId',
                    'prop_owners.owner_name AS ownerName',
                    'prop_properties.prop_address AS address',
                    'ref_prop_types.property_type AS propertyType',
                    'prop_owners.mobile_no AS mobileNo'
                )
                ->where('prop_properties.cluster_id', $request->clusterId)
                ->where('prop_properties.status', '1')
                ->get();

            if (empty($clusterDetails['0'])) {
                return $this->noData();
            }
            return $this->success($clusterDetails);
        } catch (Exception $error) {
            return $this->failure($error->getMessage());
        }
    }

    /**
     * | ----------------- saving the respective holding to the cluster ------------------------------- |
     * | @param request
     * | @var clusterDetails
     * | Operation : 385ms
     * | rating - 2
     */
    public function saveHoldingInCluster($request)
    {
        try {
            $notActive = "Not a valid cluter ID";
            $checkActiveCluster =  $this->checkActiveCluster($request->clusterId);

            return $checkActiveCluster;

            if ($checkActiveCluster == false) {
                PropProperty::where('new_holding_no', $request->holdingNo)
                    ->update([
                        'cluster_id' => $request->clusterId
                    ]);
                return $this->success($request->holdingId);
            }
            return $this->failure($notActive);
        } catch (Exception $error) {
            return $this->failure($error->getMessage());
        }
    }


    /**
     * | ----------------- calling function for the cheking of active cluster ------------------------------- |
     * | @param request
     * | @var clusterDetails
     * | Operation : 385ms
     * | rating - 2
     */
    public function checkActiveCluster($request)
    {
        // return $request;

        $checkCluster = Cluster::select('id')
            ->where('id', $request)
            ->where('status', 1)
            ->get();
        // return $checkCluster['0'];
        if (empty($checkCluster['0'])) {
            return ("0");
        }
        return ("1");
    }


































    #----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------#

    /**
     * | ----------------- Common funtion for the return components in success ------------------------------- |
     * | @param req
     * | @var mreturn
     * | Operation : returning the messge using (responseMsg)
     */
    public function success($req)
    {
        $mreturn = responseMsg(true, "Operation Success!", $req);
        return (object)$mreturn;
    }

    /**
     * | ----------------- Common funtion for the return component in failer ------------------------------- |
     * | @param req
     * | @var mreturn
     * | Operation : returning the messge using (responseMsg)
     */
    public function failure($req)
    {
        $mreturn = responseMsg(false, "Operation Failer!", $req);
        return (object)$mreturn;
    }

    /**
     * | ----------------- Common funtion for No data found in database ------------------------------- |
     * | @var mreturn
     * | Operation : returning the messge using (responseMsg)
     */
    public function noData()
    {
        $mreq = "Data Not Found!";
        $mreturn = responseMsg(false, "Operation Failer!", $mreq);
        return (object)$mreturn;
    }
}
