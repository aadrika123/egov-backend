<?php

namespace App\Models\Cluster;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cluster extends Model
{
    use HasFactory;

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
            $newCluster->user_id  = $userId;
            $newCluster->cluster_name  = $request->clusterName;
            $newCluster->cluster_type  = $request->clusterType;
            $newCluster->address  = $request->clusterAddress;
            $newCluster->mobile_no = $request->clusterMobileNo;
            $newCluster->authorized_person_name = $request->clusterAuthPersonName;
            $newCluster->save();

            return responseMsg(true, "Operaion Saved!", "");
        } catch (Exception $error) {
            return responseMsg(false, "Opearion faild!", $error->getMessage());
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

            if (null == ($request->status)) {
                Cluster::where('id', $request->id)
                    ->update([
                        'ulb_id' => $ulbId,
                        'user_id' => $userId,
                        'cluster_name' => $request->clusterName,
                        'cluster_type' => $request->clusterType,
                        'address' => $request->clusterAddress,
                        'mobile_no' => $request->clusterMobileNo,
                        'authorized_person_name' => $request->clusterAuthPersonName
                    ]);
                return responseMsg(true, "Operaion Saved without status!", "");
            }
            Cluster::where('id', $request->id)
                ->update([
                    'status' => $request->status,
                    'ulb_id' => $ulbId,
                    'user_id' => $userId,
                    'cluster_name' => $request->clusterName,
                    'cluster_type' => $request->clusterType,
                    'address' => $request->clusterAddress,
                    'mobile_no' => $request->clusterMobileNo,
                    'authorized_person_name' => $request->clusterAuthPersonName
                ]);
            return responseMsg(true, "Operaion Saved with status!", "");
        } catch (Exception $error) {
            return responseMsg(false, "Opearion faild!", $error->getMessage());
        }
    }


    /**
     * | -------------------------Get All the Cluster Detils from the Cluster Table------------------------------- |
     * | @param request
     * | @var userId
     * | @var ulbId
     * | @param error
     * | Operation : fetch all the cluster data from the Table
     * | rating - 1
     */
    public function allClusters()
    {
        return Cluster::select(
            'id',
            'cluster_name AS name',
            'cluster_type AS type',
            'address',
            'mobile_no AS mobileNo',
            'authorized_person_name AS authPersonName',
            'status'
        )
            ->where('status', 1)
            ->get();
    }
}
