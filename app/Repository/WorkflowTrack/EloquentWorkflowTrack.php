<?php

namespace App\Repository\WorkflowTrack;

use App\Repository\WorkflowTrack\WorkflowTrack;
use Illuminate\Http\Request;
use App\Models\WorkflowTrack as Track;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Repository for Workflow Track messages tracking
 */
class EloquentWorkflowTrack implements WorkflowTrack
{
    /**
     * Save workflow Track
     * @param Illuminate\Http\Request
     * @param Illuminate\Http\Request $request
     */
    public function store(Request $request)
    {
        $request->validate([
            'CitizenID' => 'required|int',
            'ModuleID' => 'required|int',
            'RefTableDotID' => 'required',
            'RefTableIDValue' => 'required',
            'Message' => 'required'
        ]);
        try {
            $track = new Track;
            $track->user_id = auth()->user()->id;
            $track->citizen_id = $request->CitizenID;
            $track->module_id = $request->ModuleID;
            $track->ref_table_dot_id = $request->RefTableDotID;
            $track->ref_table_id_value = $request->RefTableIDValue;
            $track->message = $request->Message;
            $track->track_date = date('Y-m-d H:i:s');
            $track->forwarded_to = $request->ForwardedTo;
            $track->save();
            return response()->json('Successfully Saved the Remarks', 200);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Get Workflow Track by its Workflow Id
     * @param WorkflowTrackId $id
     *  */
    public function getWorkflowTrackByID($id)
    {
        $track = DB::select("select t.id,
                            t.user_id,
                            u.user_name,
                            t.citizen_id,
                            t.module_id,
                            m.module_name,
                            t.ref_table_dot_id,
                            t.ref_table_id_value,
                            t.message,
                            t.track_date,
                            t.forwarded_to,
                            uu.user_name as forwarded_user
                            
                    from workflow_tracks t
                    left join users u on u.id=t.user_id
                    left join module_masters m on m.id=t.module_id
                    left join users uu on uu.id=t.forwarded_to
                    where t.id=$id");
        return $track;
    }
}
