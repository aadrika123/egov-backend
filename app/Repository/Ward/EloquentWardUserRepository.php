<?php

namespace App\Repository\Ward;

use App\Http\Requests\Ward\WardUserRequest;
use App\Models\Ward\WardUser;
use App\Repository\Ward\WardRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Traits\Ward;
use Illuminate\Support\Facades\Crypt;

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
     * | If status is 0 then delete the record
     * | If status is 1 then add the record
     */
    public function storeWardUser(WardUserRequest $request)
    {

        try {
            // if status is false then delete the ward
            if ($request->status == 0) {
                $wardUser = $this->checkWardUserExisting($request);         // check ward user existing using trait
                if ($wardUser) {
                    $wardUser->delete();
                    return responseMsg(true, 'Successfully disabled', "");
                } else
                    return responseMsg(true, 'Successfully Disabled', "");
            }
            // if status if true then add the ward
            if ($request->status == 1) {
                $checkExisting = $this->checkWardUserExisting($request);    // check ward user existing using trait
                if ($checkExisting) {
                    return responseMsg(true, "Successfully Enabled the Ward", "");
                }
                $wardUser = new WardUser();
                $wardUser->user_id = $request->userID;
                $wardUser->ulb_ward_id = $request->ulbWardID;
                $wardUser->is_admin = $request->isAdmin;
                $wardUser->save();
                return responseMsg(true, "Successfully Enabled the Ward", "");
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
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
}
