<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Workflows\WfWardUser;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Controller for Add, Update, View , Delete of Wf Ward User Table
 * -------------------------------------------------------------------------------------------------
 * Created On-07-10-2022
 * Created By-Mrinal Kumar
 * Modification On: 19-12-2022
 * Status : Open
 * -------------------------------------------------------------------------------------------------
 */

class WardUserController extends Controller
{
    //create WardUser
    public function createWardUser(Request $req)
    {
        try {
            $req->validate([
                'userId' => 'required',
                'wardId' => 'required',
                'isAdmin' => 'required',
            ]);
            $checkExisting = WfWardUser::where('user_id', $req->userId)
                ->where('ward_id', $req->wardId)
                ->first();
            if ($checkExisting) {
                $checkExisting->user_id = $req->userId;
                $checkExisting->ward_id = $req->wardId;
                $checkExisting->save();
                return responseMsg(true, "User Exist", "");
            }

            $create = new WfWardUser();
            $create->addWardUser($req);

            return responseMsg(true, "Successfully Saved", "");
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }

    //update WardUser
    public function updateWardUser(Request $req)
    {
        try {
            $update = new WfWardUser();
            $list  = $update->updateWardUser($req);

            return responseMsg(true, "Successfully Updated", $list);
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }

    //WardUser list by id
    public function WardUserbyId(Request $req)
    {
        try {

            $listById = new WfWardUser();
            $list  = $listById->listbyId($req);

            return responseMsg(true, "WardUser List", $list);
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }

    //all WardUser list
    public function getAllWardUser()
    {
        try {

            $list = new WfWardUser();
            $WardUsers = $list->listWardUser();

            return responseMsg(true, "All WardUser List", $WardUsers);
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }


    //delete WardUser
    public function deleteWardUser(Request $req)
    {
        try {
            $delete = new WfWardUser();
            $delete->deleteWardUser($req);

            return responseMsg(true, "Data Deleted", '');
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    public function tcList(Request $req)
    {
        $req->validate([
            'wardId' => 'nullable',
        ]);
        try {
            $ulbId =  authUser()->ulb_id;
            $TC = ['TC', 'TL', 'JSK'];

            $data = User::select(
                'users.id',
                'user_name',
                'user_type',
            )
                ->where('ulb_id', $ulbId)
                ->whereIN('user_type', $TC)
                ->get();

            if ($req->wardId) {
                $data = User::select(
                    'users.id',
                    'user_name',
                    'user_type',
                )
                    ->join('wf_ward_users', 'wf_ward_users.user_id', 'users.id')
                    ->where('ulb_id', $ulbId)
                    ->where('ward_id', $req->wardId)
                    ->whereIN('user_type', $TC)
                    ->get();
            }

            return responseMsgs(true, "TC List", remove_null($data), "010201", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
