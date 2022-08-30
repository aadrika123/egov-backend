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
        dd($request->all());
        // try {
        //     $ulb_ward = $request['ulbWardID'];
        //     foreach ($ulb_ward as $ulb_wards) {
        //         $check = WardUser::where('user_id', $request->userID)
        //             ->where('ulb_ward_id', $ulb_wards)
        //             ->first();
        //         if ($check) {
        //             return responseMsg(false, "Ward Permission Is Already Existing for User ID !! Please Edit Your Ward Permission for this User", "");
        //         }
        //         $ward_user = new WardUser();
        //         $this->savingWardUser($ward_user, $request, $ulb_wards);
        //         $ward_user->save();
        //     }
        //     return responseMsg(true, "Successfully Saved", "");
        // } catch (Exception $e) {
        //     return response()->json($e, 400);
        // }
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
     * | @param UserID $id
     * | @var ulbID current logged in user id
     * | @var query contains query for executing sql query
     * | @var wardUsers container value of executed sql query
     * | @return response
     */
    public function getWardUserByID($id)
    {

        $ulbID = auth()->user()->ulb_id;
        $query = "SELECT 
                    uwm.id AS ulb_ward_id,
                    uwm.ward_name,
                    wu.user_id,
                    wu.ulb_ward_id,
                    wu.is_admin,
                    (CASE 
                        WHEN user_id IS NOT NULL THEN TRUE
                        ELSE false
                    END) AS status

                    FROM ulb_ward_masters uwm
                    
            LEFT JOIN (SELECT * FROM ward_users WHERE user_id=$id) wu ON wu.ulb_ward_id=uwm.id

            WHERE uwm.ulb_id=$ulbID";
        $wardUsers = DB::select($query);
        return responseMsg(true, "Data Fetched", remove_null($wardUsers));
    }

    /**
     * | Get All Ward Users
     */
    public function getAllWardUsers()
    {
        $query = $this->qWardUser();
        $ward_users = DB::select($query);
        return responseMsg(true, "Data Fetched", remove_null($ward_users));
    }
}
