<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowTrack extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function saveTrack($request)
    {
        $userId = authUser()->id;
        $mTrackDate = Carbon::now()->format('Y-m-d H:i:s');
        $track = new WorkflowTrack;
        $track->workflow_id = $request->workflowId;
        $track->citizen_id = $request->citizenId;
        $track->module_id = $request->moduleId;
        $track->ref_table_dot_id = $request->refTableDotId;
        $track->ref_table_id_value = $request->refTableIdValue;
        $track->track_date = $mTrackDate;
        $track->forward_date = $request->forwardDate ?? null;
        $track->forward_time = $request->forwardTime ?? null;
        $track->message = $request->message;
        $track->sender_role_id = $request->senderRoleId ?? null;
        $track->receiver_role_id = $request->receiverRoleId ?? null;
        $track->user_id = $userId;
        $track->save();
    }

    public function detailById($req)
    {
        return WorkflowTrack::select(
            'workflow_tracks.id',
            'workflow_tracks.user_id',
            'workflow_tracks.citizen_id',
            'workflow_tracks.module_id',
            'module_masters.module_name',
            'workflow_tracks.ref_table_dot_id',
            'workflow_tracks.ref_table_id_value',
            'workflow_tracks.message',
            'workflow_tracks.track_date',
            'users.user_name'
        )
            ->join('users', 'users.id', 'workflow_tracks.user_id')
            ->join('module_masters', 'module_masters.id', 'workflow_tracks.module_id')
            ->where('workflow_tracks.id', $req->id)
            ->first();
    }

    public function detailByrefId($req)
    {
        return WorkflowTrack::select(
            'workflow_tracks.id',
            'workflow_tracks.user_id',
            'workflow_tracks.citizen_id',
            'workflow_tracks.module_id',
            'module_masters.module_name',
            'workflow_tracks.ref_table_dot_id',
            'workflow_tracks.ref_table_id_value',
            'workflow_tracks.message',
            'workflow_tracks.track_date',
            'users.user_name'
        )
            ->join('users', 'users.id', 'workflow_tracks.user_id')
            ->join('module_masters', 'module_masters.id', 'workflow_tracks.module_id')
            ->where('workflow_tracks.ref_table_dot_id', $req->refTableId)
            ->where('workflow_tracks.ref_table_id_value', $req->refTableValue)
            ->get();
    }
}
