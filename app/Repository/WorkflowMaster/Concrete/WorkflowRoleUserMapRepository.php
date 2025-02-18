<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\Interface\iWorkflowRoleUserMapRepository;
use Illuminate\Http\Request;
use App\Models\Workflows\WfRoleusermap;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;


/**
 * Repository for Save Edit and View 
 * Parent Controller -App\Controllers\WorkflowRoleUserMapController
 * -------------------------------------------------------------------------------------------------
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar
 * -------------------------------------------------------------------------------------------------
 * 
 */



class WorkflowRoleUserMapRepository implements iWorkflowRoleUserMapRepository
{
    private $_redis;
    public function __construct()
    {
        $this->_redis = Redis::connection();
    }

    public function create(Request $request)
    {
        $createdBy = Auth()->user()->id;

        try {
            $checkExisting = WfRoleusermap::where('wf_role_id', $request->wfRoleId)
                ->where('user_id', $request->userId)
                ->first();
            if ($checkExisting) {
                $checkExisting->wf_role_id = $request->wfRoleId;
                $checkExisting->user_id = $request->userId;
                $checkExisting->save();

                Redis::del('roles-user-u-' . $request->userId);
                return responseMsg(true, "User Exist", "");
            }
            // create
            $mWfRoleusermap = new WfRoleusermap;
            $mWfRoleusermap->wf_role_id = $request->wfRoleId;
            $mWfRoleusermap->user_id = $request->userId;
            $mWfRoleusermap->created_by = $createdBy;
            $mWfRoleusermap->stamp_date_time = Carbon::now();
            $mWfRoleusermap->created_at = Carbon::now();
            $mWfRoleusermap->save();

            Redis::del('roles-user-u-' . $request->userId);
            return responseMsgs(true, "Successfully Saved", "", "025851", 1.0, "", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025851", 1.0, "", "POST", 400);
        }
    }

    /**
     * GetAll data
     */
    public function list()
    {
        $data = WfRoleusermap::where('is_suspended', false)
            ->orderByDesc('id')->get();
        return responseMsgs(true, "Successfully Data Fatced", $data, "025852", 1.0, "", "", 200);
    }


    /**
     * Delete data
     */
    public function delete($id)
    {
        $data = WfRoleusermap::find($id);
        $data->delete();
        return responseMsgs(true, "Successfully Deleted", $data, "025853", 1.0, "", "", 200);
    }


    /**
     * Update data
     */
    public function update(Request $request, $id)
    {
        $createdBy = Auth()->user()->id;

        try {
            $device = WfRoleusermap::find($id);
            $device->wf_role_id = $request->wfRoleId;
            $device->user_id = $request->userId;
            $device->created_by = $createdBy;
            $device->save();
            Redis::del('roles-user-u-' . $request->userId);                                 // Flush Key of the User Role Permission
            return responseMsgs(true, "Successfully Updated", "", "025854", "", "", "", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025854", 1.0, "", "POST", 400);
        }
    }

    /**
     * list view by IDs
     */

    public function view($id)
    {
        $data = WfRoleusermap::where('id', $id)
            ->where('is_suspended', false)
            ->get();
        if ($data) {
            return responseMsgs(true, "Successfully Updated", $data, "025855", "", "", "", 200);
        } else {
            return responseMsgs(false, 'Data not found', "", "025855", 1.0, "", "POST", 400);
        }
    }

    /**
     * | Get All Permitted Roles By User ID
     * | @param Request req
     * | @var query 
     * | Status-Closed
     * | Query Run Time-400 ms
     * | Rating-1
        | handel the suspended 
     */
    public function getRolesByUserId($req)
    {
        try {
            // $roles = json_decode(Redis::get('roles-user-u-' . $req->userId));
            // if (!$roles) {
            $userId = $req->authUser()->id;
            $query = "SELECT 
                                r.id AS role_id,
                                r.role_name,
                                rum.wf_role_id,
                                (CASE 
                                WHEN rum.wf_role_id IS NOT NULL THEN TRUE 
                                ELSE 
                                FALSE END) AS permission_status,
                                rum.user_id

                        FROM wf_roles r

                LEFT JOIN (SELECT * FROM wf_roleusermaps WHERE user_id= $userId AND is_suspended = false) rum ON rum.wf_role_id=r.id
                WHERE r.is_suspended = false
                AND r.status = 1
                ";
                
            $roles = DB::select($query);
            $this->_redis->set('roles-user-u-' . $req->userId, json_encode($roles));               // Caching Should Be flush on New role Permission to the user
            // }
            return responseMsgs(true, "Role Permissions", remove_null($roles), "025856", 1.0, "", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025856", 1.0, "", "POST", 400);
        }
    }

    /**
     * | Enable or Disable the User Role Permission
     * | @param req
     * | Status-closed
     * | RunTime Complexity-353 ms
     * | Rating-2
     */
    public function updateUserRoles($req)
    {
        try {
            Redis::del('roles-user-u-' . $req->userId);                                 // Flush Key of the User Role Permission

            $userRoles = WfRoleusermap::where('wf_role_id', $req->roleId)
                ->where('user_id', $req->userId)
                ->first();

            if ($userRoles) {                                                           // If Data Already Existing
                switch ($req->status) {
                    case 1:
                        $userRoles->is_suspended = 0;
                        $userRoles->save();
                        return responseMsg(true, "Successfully Enabled the Role Permission for User", "");
                        // break;
                    case 0:
                        $userRoles->is_suspended = 1;
                        $userRoles->save();
                        return responseMsg(true, "Successfully Disabled the Role Permission", "");
                        // break;
                }
            }

            $userRoles = new WfRoleusermap();
            $userRoles->wf_role_id = $req->roleId;
            $userRoles->user_id = $req->userId;
            $userRoles->created_by = $req->authUser()->id;
            $userRoles->save();

            return responseMsgs(true, "Successfully Enabled the Role Permission for the User", "", "025857", 1.0, "", "POST", 200);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "025857", 1.0, "", "POST", 400);
        }
    }

    //role of logged in user
    public function roleUser($req)
    {
        $userId = $req->authUser()->id;
        $role = WfRoleusermap::select('wf_roleusermaps.*')
            ->where('user_id', $userId)
            ->where('is_suspended', false)
            ->get();
        return $role;
    }
}
