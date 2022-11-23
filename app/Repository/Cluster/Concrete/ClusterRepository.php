<?php

namespace App\Repository\Cluster\Concrete;

use App\Models\Cluster\Cluster;
use App\Repository\Cluster\Interfaces\iCluster;
use Exception;


class ClusterRepository implements iCluster
{

    /**
     * | ----------------- Collecting all data of the cluster/returning ------------------------------- |
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
     * | ----------------- Collecting all data of the cluster according to id /returning ------------------------------- |
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
     * | ------------------------- updating the cluster data ------------------------------- |
     * | @param request
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
     * | ----------------- saving the data of the cluster/ ------------------------------- |
     * | @param request
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
     * | ----------------- deleting the data of the cluster ------------------------------- |
     * | @param request
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
     * | ----------------- Common funtion for the return components in success ------------------------------- |
     * | @param req
     * | @var mreturn
     * | Operation : returning the messge using (responseMsg)
     */
    // public function holdingDetailsById($request)
    // {

    // }



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
     * | @param req
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
