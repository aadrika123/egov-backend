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
            $mdetails = new Cluster();
            $mdetails = $mdetails->allClusters();
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
            $mdetails = new Cluster();
            $mdetailsById = $mdetails->allClusters()
                ->where('id', $request->id)
                ->first();

            if (!empty($mdetailsById)) {
                return $this->success($mdetailsById);
            }
            return  $this->noData();
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
     * | Operation : returning details according to the holdin no 
     * |
     * | Rating : 2
     * | Time :
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
     * | Operation : returning the details according to the cluster Id
     * | Time: 385ms
     * | Rating - 2
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
     * | @var notActive
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
        return $mreturn;
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
