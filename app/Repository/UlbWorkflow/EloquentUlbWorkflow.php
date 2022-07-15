<?php

namespace App\Repository\UlbWorkflow;

use App\Repository\UlbWorkflow\UlbWorkflow;
use App\Models\UlbWorkflowMaster;
use Illuminate\Http\Request;
use Exception;
use App\Traits\UlbWorkflow as UlbWorkflowTrait;
use Illuminate\Support\Facades\DB;

/**
 * Repository for Ulb Workflows Store, fetch, edit and destroy
 * Created On-14-07-2022 
 * Created By-Anshu Kumar
 */

class EloquentUlbWorkflow implements UlbWorkflow
{
    use UlbWorkflowTrait;
    /**
     * Storing UlbWorkflows
     * Storing Using Trait-App\Traits\UlbWorkflow
     * @param Illuminate\Http\Request
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public function store(Request $request)
    {
        $request->validate([
            'UlbID' => 'required',
            'workflow_id' => "required|int"
        ]);

        try {
            $ulb_workflow = new UlbWorkflowMaster;
            $stmt = $this->checkExisting($request);    // Checking if the ulbID already existing for the workflowid or not
            if ($stmt) {
                return response()->json('Workflow is already existing to this Ulb ID', 400);
            } else {
                $this->saving($ulb_workflow, $request);
                return response()->json('Successfully Saved the Ulb Workflow', 200);
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * 
     */
    public function create()
    {
        $data = DB::select("
                select  uwm.*,
                        um.ulb_name,
                        w.workflow_name
                        from ulb_workflow_masters uwm
                        left join ulb_masters um on um.id=uwm.ulb_id
                        left join workflows w on w.id=uwm.workflow_id
                        where uwm.deleted_at is null
                        order by uwm.id desc
                ");
        return $data;
    }

    /**
     * Updating UlbWorkflows
     * Store Using App\Traits\UlbWorkflow
     * @param Illuminate\Http\Request
     * @param Illuminate\Http\Request $request
     * @return response
     */

    public function update(Request $request, $id)
    {
        $request->validate([
            'UlbID' => 'required',
            'workflow_id' => 'required|int'
        ]);

        try {
            $ulb_workflow = UlbWorkflowMaster::find($id);
            $stmt = $ulb_workflow->workflow_id == $request->workflow_id;
            if ($stmt) {
                $this->saving($ulb_workflow, $request);
                return response()->json('Successfully Updated the Ulb Workflow', 200);
            }
            if (!$stmt) {
                $check_workflow = $this->checkExisting($request);      // Checking if the ulb_workflow already existing or not
                if ($check_workflow) {
                    return response()->json('Workflow already Existing for this Ulb', 400);
                } else {
                    $this->saving($ulb_workflow, $request);
                    return response()->json('Successfully Updated', 200);
                }
            }
            return response()->json('Successfully Updated');
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Deleting Temporary UlbWorkflows 
     * @param id
     */
    public function destroy($id)
    {
        try {
            $ulb_workflow = UlbWorkflowMaster::find($id);
            $ulb_workflow->delete();
            return response()->json('Successfully Deleted', 200);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Get UlbWorkflows By Id
     * @param id
     */
    public function show($id)
    {
        $data = DB::select("
        select  uwm.*,
                um.ulb_name,
                w.workflow_name
                from ulb_workflow_masters uwm
                left join ulb_masters um on um.id=uwm.ulb_id
                left join workflows w on w.id=uwm.workflow_id
            where uwm.id=$id
        ");
        if ($data) {
            return $data;
        } else {
            return response()->json('Data not found', 400);
        }
    }
}
