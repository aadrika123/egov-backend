<?php

namespace App\Repository\UlbWorkflow;

use App\Repository\UlbWorkflow\UlbWorkflow;
use App\Models\UlbWorkflowMaster;
use Illuminate\Http\Request;
use Exception;
use App\Traits\UlbWorkflow as UlbWorkflowTrait;

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
            'workflow_id' => "required|int|unique:ulb_workflow_masters"
        ]);

        try {
            $ulb_workflow = new UlbWorkflowMaster;
            $this->saving($ulb_workflow, $request);
            return response()->json('Successfully Saved the Ulb Workflow', 200);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
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
            'workflow_id' => 'required|int'
        ]);

        try {
            $ulb_workflow = UlbWorkflowMaster::find($id);
            $stmt = $ulb_workflow->workflow_id == $request->workflow_id;
            if ($stmt) {
                $this->saving($ulb_workflow, $request);
                return response()->json('Successfully Updated', 200);
            }
            if (!$stmt) {
                $check_workflow = UlbWorkflowMaster::where('workflow_id', '=', $request->workflow_id)
                    ->get();
                if ($check_workflow) {
                    return response()->json('UlbWorkflow already Existing', 400);
                }
                if (!$check_workflow) {
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
        $data = UlbWorkflowMaster::find($id);
        if ($data) {
            return $data;
        } else {
            return response()->json('Data not found', 400);
        }
    }
}
