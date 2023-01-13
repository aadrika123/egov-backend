<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

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
        $track->message = $request->comment;
        $track->forward_date = Carbon::now()->format('Y-m-d') ?? null;
        $track->forward_time = Carbon::now()->format('H:i:s') ?? null;
        $track->sender_role_id = $request->senderRoleId ?? null;
        $track->receiver_role_id = $request->receiverRoleId ?? null;
        $track->verification_status = $request->verificationStatus;
        $track->user_id = $userId;
        $track->save();
    }

    public function details()
    {
        return  DB::table('workflow_tracks')
            ->select(
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
            ->join('module_masters', 'module_masters.id', 'workflow_tracks.module_id');
    }

    /**
     * | Get Tracks by Ref Table Id
     */
    public function getTracksByRefId($mRefTable, $tableId)
    {
        return DB::table('workflow_tracks')
            ->select(
                'workflow_tracks.ref_table_dot_id AS referenceTable',
                'workflow_tracks.ref_table_id_value AS applicationId',
                'workflow_tracks.message',
                'workflow_tracks.track_date',
                'workflow_tracks.forward_date',
                'workflow_tracks.forward_time',
                'w.role_name as commentedBy',
                'wr.role_name as forwarded_to'
            )
            ->where('ref_table_dot_id', $mRefTable)
            ->where('ref_table_id_value', $tableId)
            ->join('wf_roles as w', 'w.id', '=', 'workflow_tracks.sender_role_id')
            ->leftJoin('wf_roles as wr', 'wr.id', '=', 'workflow_tracks.receiver_role_id')
            ->where('citizen_id', null)
            ->orderByDesc('workflow_tracks.id')
            ->get();
    }

    /**
     * | Get Citizen Comment
     */
    public function getCitizenTracks($mRefTable, $tableId, $citizenId)
    {
        return DB::table('workflow_tracks')
            ->select(
                'workflow_tracks.ref_table_dot_id AS referenceTable',
                'workflow_tracks.ref_table_id_value AS applicationId',
                'workflow_tracks.message',
                'workflow_tracks.track_date',
                'workflow_tracks.forward_date',
                'workflow_tracks.forward_time',
                'u.user_name as commentedBy'
            )
            ->where('ref_table_dot_id', $mRefTable)
            ->where('ref_table_id_value', $tableId)
            ->where('citizen_id', $citizenId)
            ->leftJoin('users as u', 'u.id', '=', 'workflow_tracks.citizen_id')
            ->get();
    }
}
