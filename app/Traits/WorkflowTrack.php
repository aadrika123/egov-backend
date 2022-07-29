<?php

namespace App\Traits;

/**
 *| @desc-Workflow Tracking Messages Trait 
 *| Created On-29-07-2022 
 *| Created By-Anshu Kumar
 *------------------------------------------------------------------------------------------
 *| Code Tested By-
 *| Code Testing Date-
 */

trait WorkflowTrack
{
    // Query references for required fields for workflow tracking

    public function refQuery()
    {
        $query = "SELECT t.id,
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
                    
            FROM workflow_tracks t
            LEFT JOIN users u on u.id=t.user_id
            LEFT JOIN module_masters m on m.id=t.module_id
            LEFT JOIN users uu on uu.id=t.forwarded_to ";
        return $query;
    }

    // Fetching Data in array format
    public function fetchData($arr, $track)
    {
        foreach ($track as $tracks) {
            $val['id'] = $tracks->id ?? '';
            $val['user_id'] = $tracks->user_id ?? '';
            $val['user_name'] = $tracks->user_name ?? '';
            $val['citizen_id'] = $tracks->citizen_id ?? '';
            $val['module_id'] = $tracks->module_id ?? '';
            $val['module_name'] = $tracks->module_name ?? '';
            $val['ref_table_dot_id'] = $tracks->ref_table_dot_id ?? '';
            $val['ref_table_id_value'] = $tracks->ref_table_id_value ?? '';
            $val['message'] = $tracks->message ?? '';
            $val['track_date'] = $tracks->track_date ?? '';
            $val['forwarded_to'] = $tracks->forwarded_to ?? '';
            $val['forwarded_user'] = $tracks->forwarded_user ?? '';
            array_push($arr, $val);
        }
        return response()->json($arr, 200);
    }
}
