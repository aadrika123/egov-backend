<?php

namespace App\Models\Property;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Exception;

class PropActiveConcession extends Model
{
    use HasFactory;

    /**
     * | Get Concession Details
     */
    public function getDetailsById($id)
    {
        $details = PropActiveConcession::select(
            'prop_active_concessions.*',
            'prop_active_concessions.applicant_name as owner_name',
            's.*',
            'u.ward_name as old_ward_no',
            'u1.ward_name as new_ward_no',
            'p.property_type',
            'o.ownership_type',
            'r.road_type as road_type_master'
        )
            ->leftJoin('prop_properties as s', 's.id', '=', 'prop_active_concessions.property_id')
            ->leftJoin('ulb_ward_masters as u', 'u.id', '=', 's.ward_mstr_id')
            ->leftJoin('ulb_ward_masters as u1', 'u.id', '=', 's.new_ward_mstr_id')
            ->leftJoin('ref_prop_ownership_types as o', 'o.id', '=', 's.ownership_type_mstr_id')
            ->leftJoin('ref_prop_types as p', 'p.id', '=', 's.prop_type_mstr_id')
            ->leftJoin('ref_prop_road_types as r', 'r.id', '=', 's.road_type_mstr_id')
            ->where('prop_active_concessions.id', $id)
            ->first();
        return $details;
    }

    /**
     * | Get Concession by Id
     */
    public function getConcessionById($id)
    {
        return PropActiveConcession::find($id);
    }

    /**
     * | Get Concession Application Dtls by application No
     */
    public function getDtlsByConcessionNo($concessionNo)
    {
        return DB::table('prop_active_concessions as c')
            ->select(
                'c.id',
                'c.application_no',
                'c.applicant_name as owner_name',
                'p.new_holding_no',
                'p.ward_mstr_id',
                'p.new_ward_mstr_id',
                'u.ward_name as old_ward_no',
                'u1.ward_name as new_ward_no',
                'c.mobile_no'
            )
            ->join('prop_properties as p', 'p.id', '=', 'c.property_id')
            ->join('ulb_ward_masters as u', 'p.ward_mstr_id', '=', 'u.id')
            ->leftJoin('ulb_ward_masters as u1', 'p.new_ward_mstr_id', '=', 'u1.id')
            ->where('c.application_no', $concessionNo)
            ->first();
    }

    /**
     * |-------------------------- details of all concession according id -----------------------------------------------
     * | @param request
     */
    public function allConcession($request)
    {
        $concession = PropActiveConcession::where('id', $request->id)
            ->get();
        return $concession;
    }

    //concession number generation
    public function concessionNo($id)
    {
        try {
            $count = PropActiveConcession::where('id', $id)
                ->select('id')
                ->get();
            $concessionNo = 'CON' . "/" . str_pad($count['0']->id, 5, '0', STR_PAD_LEFT);

            return $concessionNo;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }


    public function escalate($req)
    {
        $userId = auth()->user()->id;
        if ($req->escalateStatus == 1) {
            $concession = PropActiveConcession::find($req->id);
            $concession->is_escalate = 1;
            $concession->escalated_by = $userId;
            $concession->save();
            return "Successfully Escalated the application";
        }
        if ($req->escalateStatus == 0) {
            $concession = PropActiveConcession::find($req->id);
            $concession->is_escalate = 0;
            $concession->escalated_by = null;
            $concession->save();
            return "Successfully De-Escalated the application";
        }
    }


    public function getConcessionNo($conId)
    {
        return PropActiveConcession::select('*')
            ->where('id', $conId)
            ->first();
    }

    /**
     * | today applied application
     */
    public function todayAppliedApplications($userId)
    {
        $date = Carbon::now();
        return PropActiveConcession::select(
            'id'
        )
            ->where('user_id', $userId)
            ->where('date', $date);
    }

    /**
     * | REcent Applications
     */
    public function recentApplication($userId)
    {
        return PropActiveConcession::select(
            'application_no as applicationNo',
            'date as applyDate',
            'applied_for as assessmentType',
            "applicant_name as applicantname",
        )
            ->where('prop_active_concessions.user_id', $userId)
            ->orderBydesc('prop_active_concessions.id')
            ->take(10)
            ->get();
    }

    /**
     * | Today Received Appklication
     */
    public function todayReceivedApplication($currentRole, $ulbId)
    {
        $date = Carbon::now()->format('Y-m-d');
        return PropActiveConcession::select(
            'application_no as applicationNo',
            'date as applyDate',
        )

            ->join('workflow_tracks', 'workflow_tracks.ref_table_id_value', 'prop_active_concessions.id')
            ->where('prop_active_concessions.current_role', $currentRole)
            ->where('workflow_tracks.ulb_id', $ulbId)
            ->where('ref_table_dot_id', 'prop_active_concessions.id')
            ->whereRaw("date(track_date) = '$date'")
            ->orderBydesc('prop_active_concessions.id')
            ->get();
    }
}
