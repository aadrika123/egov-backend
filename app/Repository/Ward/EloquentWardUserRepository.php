<?php

namespace App\Repository\Ward;

use App\Http\Requests\Ward\WardUserRequest;
use App\Models\Ward\WardUser;
use App\Repository\Ward\WardRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Traits\Ward;
use Illuminate\Support\Facades\Redis;

/**
 * | Created On-20-08-2022 
 * | Created By-Anshu Kumar
 * ------------------------------------------------------------------------------------
 * | Ward User Crud Operations
 */


class EloquentWardUserRepository implements WardRepository
{
    use Ward;
    /**
     * | Store Ward User
     * | @param WardUserRequest 
     * | @param WardUserRequest $request
     * | @return Response
     * ------------------------------------------------------
     * | $ulb_ward > Contains the Array for the ulbWard
     * | $check > Check the already existance for the ulb_ward and userID
     * | Save The Data
     */
    public function storeWardUser(WardUserRequest $request)
    {
        try {
            $ulb_ward = $request['ulbWardID'];
            foreach ($ulb_ward as $ulb_wards) {
                $check = WardUser::where('user_id', $request->userID)
                    ->where('ulb_ward_id', $ulb_wards)
                    ->first();
                if ($check) {
                    return responseMsg(false, "Ward Permission Is Already Existing for User ID !! Please Edit Your Ward Permission for this User", "");
                }
                $ward_user = new WardUser();
                $this->savingWardUser($ward_user, $request, $ulb_wards);
                $ward_user->save();
            }
            return responseMsg(true, "Successfully Saved", "");
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * | Update Ward User
     * | @param WardUserRequest $request
     * | Delete the existance for the given userID and fresh new add the ulbWardIDS
     */
    public function updateWardUser(WardUserRequest $request)
    {
        try {
            DB::beginTransaction();
            // Check existances data
            $del_exist = WardUser::where('user_id', $request->userID)->get();
            foreach ($del_exist as $del_exists) {
                $del_exists->delete();
                $redis_conn = Redis::connection();
                $redis_conn->del('ward_user:' . $del_exists->id);        // Deleting Redis Cache
            }
            // Fresh Save
            $ulb_ward = $request['ulbWardID'];
            foreach ($ulb_ward as $ulb_wards) {
                $ward_user = new WardUser();
                $this->savingWardUser($ward_user, $request, $ulb_wards);
                $ward_user->save();
            }
            DB::commit();
            return responseMsg(true, "Successfully Updated", "");
        } catch (Exception $e) {
            DB::rollBack();
            return response($e);
        }
    }

    /**
     * | Get Ward Users By ID
     * | @param WardUserID $id
     * | #query > Query Statement for Fetching data regarding ward users
     * | #ward_user > Establish the DB Query
     * | #redis_conn > Establish Redis Connection
     * | #fWardUser > Find Ward User Existance
     * | @return response
     */
    public function getWardUserByID($id)
    {
        $redis_conn = Redis::connection();
        // If Redis Exists
        $redis_existance = $redis_conn->get('ward_user:' . $id);
        if ($redis_existance) {
            return responseMsg(true, "Data Fetched", json_decode($redis_existance));
        }
        $query = $this->qWardUser() . " where w.id=$id";
        $ward_user = DB::select($query);
        // Set Key on Redis
        $redis_conn->set(
            'ward_user:' . $id,
            json_encode([
                'id' => $ward_user[0]->id ?? '',
                'user_id' => $ward_user[0]->user_id ?? '',
                'user_name' => $ward_user[0]->user_name ?? '',
                'ulb_ward_id' => $ward_user[0]->ulb_ward_id ?? '',
                'is_admin' => $ward_user[0]->is_admin ?? '',
                'ulb_id' => $ward_user[0]->ulb_id ?? '',
                'ulb_name' => $ward_user[0]->ulb_name ?? '',
                'ward_name' => $ward_user[0]->ward_name ?? '',
                'old_ward_name' => $ward_user[0]->old_ward_name ?? '',
            ])
        );
        return responseMsg(true, "Data Fetched", $ward_user[0]);
    }

    /**
     * | Get All Ward Users
     */
    public function getAllWardUsers()
    {
        $query = $this->qWardUser();
        $ward_users = DB::select($query);
        return responseMsg(true, "Data Fetched", $ward_users);
    }
}
