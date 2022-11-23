<?php

namespace App\Repository\Cluster\Concrete;

use App\Models\Cluster\Cluster;
use App\Models\Property\PropProperty;
use App\Repository\Cluster\Interfaces\iCluster;
use Error;
use Exception;


class ClusterRepository implements iCluster
{

    /**
     * | ----------------- Collecting all data of the cluster/returning/master ------------------------------- |
     * | @var mdetails
     * | Operation : read table cluster and returning all active data
     * | rating - 1
     * | operation time - 320 ms 
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
                ->get();
            return $this->success($mdetails);
        } catch (Exception $error) {
            return $this->failure($error->getMessage());
        }
    }

    /**
     * | ----------------- Collecting all data of the cluster according to cluster id /returning/master ------------------------------- |
     * | @var mdetails
     * | Operation : read table cluster and returning the data according to id  
     * | rating - 1
     * | time - 385 ms
     */
    public function getClusterById($request)
    {

        try {
            $mdetailsById = Cluster::select(
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

            if (!null == ($mdetailsById) && $mdetailsById->status == 1) {
                return $this->success($mdetailsById);
            }
            return  $this->noData();
        } catch (Exception $error) {
            return $this->failure($error->getMessage());
        }
    }


    /**
     * | ------------------------- updating the cluster data according to cluster id/master ------------------------------- |
     * | @param request
     * | @var userId
     * | @var ulbId
     * | @param error
     * | Operation : updating the cluster data whith new data
     * | rating - 1
     * | time - 428 ms
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
     * | ----------------- saving new data in the cluster/master ------------------------------- |
     * | @param request
     * | @param error
     * | @var userId
     * | @var ulbId
     * | @var newCluster
     * | Operation : saving the data of cluster   
     * | rating - 1
     * | time - 477
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
     * | @param request
     * | @param error
     * | Operation : soft delete of the respective detail 
     * | rating - 1
     * | time - 420
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
                ->join('ref_prop_types', 'ref_prop_types.id', '=', 'prop_properties.prop_type_mstr_id')
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

            if ($checkActiveCluster == "1") {
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
     * | @var checkCluster
     * | Operation : finding cluster Active
     * | rating - 1
     */
    public function checkActiveCluster($request)
    {
        try {
            $checkCluster = Cluster::select('id')
                ->where('id', $request)
                ->where('status', 1)
                ->get();
            if (empty($checkCluster['0'])) {
                return ("0");
            }
            return ("1");
        } catch (Exception) {
            return ("0");
        }
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
