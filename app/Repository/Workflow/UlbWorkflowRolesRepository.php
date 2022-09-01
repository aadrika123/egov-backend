<?php

namespace App\Repository\Workflow;

use App\Models\UlbWorkflowMaster;
use App\Models\Workflows\UlbWorkflowRole;
use App\Repository\Workflow\iWorkflowRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/*
| ---------------------------------------------------------------------------------------------------
| Created On-23-08-2022 
| Created By- Anshu Kumar
| Workflow Wise Roles Crud Operations
| ----------------------------------------------------------------------------------------------------
*/

class UlbWorkflowRolesRepository implements iWorkflowRepository
{
    /**
     * | Get All Roles by Ulb Workflow ID
     * | @param Request $request
     * | @var refUlbWorkflowID > Get the id of UlbWorkflowMaster
     * | @var query the db query 
     * | @var result the resulant data
     */
    public function getAllRolesByUlbWorkflowID(Request $request)
    {
        $refUlbWorkflowID = UlbWorkflowMaster::where('ulb_id', $request->ulbID)
            ->where('workflow_id', $request->workflowID)
            ->first();

        $query = "SELECT rm.id AS role_id,
                        rm.role_name,
                        rm.role_description,
                        rm.ulb_id,
                        (CASE 
                        WHEN role_id IS NOT NULL THEN true
                        ELSE false
                        END) AS permission_status
                    FROM role_masters rm


                LEFT JOIN (SELECT * FROM ulb_workflow_roles WHERE ulb_workflow_id=$refUlbWorkflowID->id) uwr ON uwr.role_id=rm.id

                WHERE rm.ulb_id=$request->ulbID";
        $result = DB::select($query);
        return responseMsg(true, "Data Fetched", remove_null($result));
    }

    /**
     * | Store Request Resource In DB
     * | @param Request
     * | @param Request $request
     */
    public function store(Request $request)
    {
        $refUlbWorkflowID = UlbWorkflowMaster::where('ulb_id', $request->ulbID)
            ->where('workflow_id', $request->workflowID)
            ->first();
        if ($request->status == 1) {
            $role = new UlbWorkflowRole();
            $role->ulb_workflow_id = $refUlbWorkflowID->id;
            $role->role_id = $request->roleID;
            $role->save();
        }
    }
}
