<?php

namespace App\Repository\WorkflowTrack;

use App\Repository\WorkflowTrack\WorkflowTrack;
use Illuminate\Http\Request;
use App\Models\WorkflowTrack as Track;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Traits\WorkflowTrack as TrackTrait;

/**
 * Repository for Workflow Track messages tracking
 */

class EloquentWorkflowTrack implements WorkflowTrack
{
    use TrackTrait;
    /**
     * Save workflow Track
     * @param Illuminate\Http\Request
     * @param Illuminate\Http\Request $request
     */
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required',
        ]);
        try {
            $track = new Track;
            $track->workflow_candidate_id = $request->workflowCandidateID;
            $track->citizen_id = $request->citizenID;
            $track->module_id = $request->moduleID;
            $track->ref_table_dot_id = $request->refTableDotID;
            $track->ref_table_id_value = $request->refTableIDValue;
            $track->message = $request->message;
            $track->track_date = date('Y-m-d H:i:s');
            $track->forwarded_to = $request->forwardedTo;
            $track->save();
            $message = ["status" => true, "message" => "Successfully Saved The Remarks", "data" => ''];
            return response()->json($message, 200);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     *| Get Workflow Track by its Workflow Id
     *| @param WorkflowTrackId $id
     *| @return response
     *|---------------------------------------------------------------------------------------
     *| DEFINING VARIABLE
     *| --------------------------------------------------------------------------------------
     *| #ref_table_stmt= select operation for selecting required fields
     *| #conditional_stmt= Condition for Select operation with where
     *| #query = concating both variables and establish final query result 
     *| @return fetchData() as trait function 
     *| --------------------------------------------------------------------------------------
     */
    public function getWorkflowTrackByID($id)
    {
        $ref_table_stmt = $this->refQuery();                            // Trait function for Select operations
        $conditional_stmt = "where t.id=$id";
        $query = $ref_table_stmt . $conditional_stmt;
        $track = DB::select($query);
        $arr = array();
        return $this->fetchData($arr, $track);                             // Trait function for Fetch data on array format
    }

    /**
     *| Get WorkflowTrack By Reference Table ID and Reference Table Value
     *| @param ReferenceTableID $ref_table_id
     *| @param ReferenceTableValue $refereceTableValue
     *|---------------------------------------------------------------------------------------
     *| DEFINING VARIABLE
     *| --------------------------------------------------------------------------------------
     *| #ref_table_stmt= select operation for selecting required fields
     *| #conditional_stmt= Condition for Select operation with where
     *| #query = concating both variables and establish final query result 
     *| $arr = array that contains all the response payload
     *| @return fetchData() as trait function 
     *| --------------------------------------------------------------------------------------
     */
    public function getWorkflowTrackByTableIDValue($ref_table_id, $ref_table_value)
    {
        $ref_table_stmt = $this->refQuery();                        // Trait function for Select operations
        $conditional_stmt = "WHERE t.ref_table_dot_id='$ref_table_id' AND t.ref_table_id_value='$ref_table_value' ORDER BY id DESC";
        $query = $ref_table_stmt . $conditional_stmt;
        $track = DB::select($query);
        $arr = array();
        return $this->fetchData($arr, $track);                      // Trait function for Fetch data on array format
    }

    //changes by Mrinal
    //Date:12/11/2022
    //notification by citixen id

    public function getNotificationByCitizenId(Request $request)
    {
        $citizen_id = Auth()->user()->id;
        $notification  = Track::where('citizen_id', $citizen_id)
            ->select(
                'workflow_id',
                'module_id',
                'ref_table_dot_id',
                'ref_table_id_value',
                'message',
                'workflow_tracks.created_at as track_date',
                'user_name'
            )
            ->join('users', 'users.id', '=', 'workflow_tracks.commented_by')
            ->where('workflow_id', $request->workflowId)
            ->where('ref_table_id_value', $request->id)
            ->get();
        return responseMsg(true, "Data Retrived", $notification);
    }
}
