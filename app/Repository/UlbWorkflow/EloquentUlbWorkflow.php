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
     * --------------------------------------------------------------------------------------
     * @desc Check the duplication of Module for the UlbID 
     * #check_ulb_module=Statement for checking the already existance for UlbID and ModuleID
     * --------------------------------------------------------------------------------------
     * Save Using Trait
     */
    public function store(Request $request)
    {
        $request->validate([
            'ulbID' => 'required',
            'workflowID' => "required|int"
        ]);

        try {
            $ulb_workflow = new UlbWorkflowMaster;
            $check_ulb_module = $this->checkUlbModuleExistance($request);    // Checking if the ulbID already existing for the workflowid or not
            if ($check_ulb_module) {
                return response()->json('Module is already existing to this Ulb ID', 400);
            }
            if (!$check_ulb_module) {
                return $this->saving($ulb_workflow, $request);
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Show all Ulb Workflows
     */
    public function create()
    {
        $ulb_workflow = DB::select("
                select  uwm.*,
                        um.ulb_name,
                        w.workflow_name
                        from ulb_workflow_masters uwm
                        left join ulb_masters um on um.id=uwm.ulb_id
                        left join workflows w on w.id=uwm.workflow_id
                        where uwm.deleted_at is null
                        order by uwm.id desc
                ");
        $arr = array();
        return $this->fetchUlbWorkflow($ulb_workflow, $arr);
    }

    /**
     * Updating UlbWorkflows
     * Store Using App\Traits\UlbWorkflow
     * @param Illuminate\Http\Request
     * @param Illuminate\Http\Request $request
     * @return response
     * -------------------------------------------------------------------------------------
     * #stmt= Statement for checking already existance of Module ID for UlbID
     * Update Ulb Workflow Masters
     * In case of Workflow Candidates First delete the existing records of UlbWorkflowCandidates and Then add New
     * 
     */

    public function update(Request $request, $id)
    {
        $request->validate([
            'ulbID' => 'required',
            'workflowID' => 'required|int'
        ]);

        try {
            $ulb_workflow = UlbWorkflowMaster::find($id);
            $stmt = $ulb_workflow->module_id == $request->moduleID;
            if ($stmt) {
                // $this->saving($ulb_workflow, $request);
                $this->deleteExistingCandidates($id);
                return $this->saving($ulb_workflow, $request);
            }
            if (!$stmt) {
                $check_module = $this->checkUlbModuleExistance($request);      // Checking if the ulb_workflow already existing or not
                if ($check_module) {
                    return response()->json('Module already Existing for this Ulb', 400);
                } else {
                    $this->deleteExistingCandidates($id);                       // Deleting Existing Candidates
                    return $this->saving($ulb_workflow, $request);
                }
            }
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
        $ulb_workflow = DB::select("
        select  uwm.*,
                um.ulb_name,
                w.workflow_name
                from ulb_workflow_masters uwm
                left join ulb_masters um on um.id=uwm.ulb_id
                left join workflows w on w.id=uwm.workflow_id
            where uwm.id=$id
        ");
        if ($ulb_workflow) {
            $arr = array();
            return $this->fetchUlbWorkflow($ulb_workflow, $arr);
        } else {
            return response()->json('Data not found', 400);
        }
    }

    /**
     * Display the Specific record of Ulb Workflows by their Ulbs
     * 
     * @param int $ulb_id
     * @return \Illuminate\Http\Response
     */

    public function getUlbWorkflowByUlbID($ulb_id)
    {
        $workflow = DB::select("
                                select u.id,
                                um.ulb_name,
                                u.workflow_id,
                                w.workflow_name,
                                u.initiator,
                                u.finisher,
                                u.remarks
                        from ulb_workflow_masters u
                        left join workflows w on w.id=u.workflow_id
                        left join ulb_masters um on um.id=u.ulb_id
                        where u.ulb_id=$ulb_id and u.deleted_at is null
                    ");
        return response()->json($workflow, 200);
    }
}
