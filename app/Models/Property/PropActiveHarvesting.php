<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropActiveHarvesting extends Model
{
    use HasFactory;

    /**
     * | Get Harvesting List
     * | function for the harvesting list according to ulb/user details
     */
    public function getHarvestingList($ulbId)
    {
        return PropActiveHarvesting::select(
            'prop_active_harvestings.id',
            'prop_active_harvestings.application_no',
            DB::raw("string_agg(owner_name,',') as applicant_name"),
            'a.ward_mstr_id',
            'u.ward_name as ward_no',
            'a.holding_no',
            'a.prop_type_mstr_id',
            'p.property_type',
            'prop_active_harvestings.workflow_id',
            'prop_active_harvestings.current_role as role_id'
        )
            ->join('prop_properties as a', 'a.id', '=', 'prop_active_harvestings.property_id')
            ->join('prop_owners', 'prop_owners.property_id', 'a.id')
            ->join('ref_prop_types as p', 'p.id', '=', 'a.prop_type_mstr_id')
            ->join('ulb_ward_masters as u', 'u.id', '=', 'a.ward_mstr_id')
            ->where('prop_active_harvestings.status', 1)
            ->where('prop_active_harvestings.ulb_id', $ulbId)
            ->groupBy(
                'prop_active_harvestings.id',
                'a.ward_mstr_id',
                'u.ward_name',
                'a.holding_no',
                'a.prop_type_mstr_id',
                'p.property_type'
            );
    }

    public function saves($request, $ulbWorkflowId, $initiatorRoleId, $finisherRoleId,  $userId, $citizenId)
    {

        $waterHaravesting = new PropActiveHarvesting();
        $waterHaravesting->property_id = $request->propertyId;
        $waterHaravesting->harvesting_status = $request->isWaterHarvestingBefore;
        $waterHaravesting->date_of_completion  =  $request->dateOfCompletion;
        $waterHaravesting->workflow_id = $ulbWorkflowId->id;
        $waterHaravesting->current_role = collect($initiatorRoleId)->first()->role_id;
        $waterHaravesting->initiator_role_id = collect($initiatorRoleId)->first()->role_id;
        $waterHaravesting->finisher_role_id = collect($finisherRoleId)->first()->role_id;
        $waterHaravesting->citizen_id = $citizenId ?? null;
        $waterHaravesting->user_id = $userId ?? null;
        $waterHaravesting->ulb_id = $request->ulbId;
        $waterHaravesting->save();

        $harvestingNo = $this->harvestingNo($waterHaravesting->id);

        PropActiveHarvesting::where('id', $waterHaravesting->id)
            ->update(['application_no' => $harvestingNo]);

        $waterHaravestingDtl = PropActiveHarvesting::where('id', $waterHaravesting->id)
            ->first();
        return $waterHaravestingDtl;
    }

    /**
     * | Get Harvesting Details By Id
     */
    public function getDetailsById($id)
    {
        return DB::table('prop_active_harvestings as h')
            ->select(
                'h.*',
                'h.user_id as citizen_user_id',
                'pp.*',
                'w.ward_name as old_ward_no',
                'nw.ward_name as new_ward_no',
                'o.ownership_type',
                'p.property_type',
                'r.road_type as road_type_master',
                'wr.role_name as current_role_name'
            )
            ->leftJoin('prop_properties as pp', 'pp.id', '=', 'h.property_id')
            ->leftJoin('ulb_ward_masters as w', 'w.id', '=', 'pp.ward_mstr_id')
            ->leftJoin('wf_roles as wr', 'wr.id', '=', 'h.current_role')
            ->leftJoin('ulb_ward_masters as nw', 'nw.id', '=', 'pp.new_ward_mstr_id')
            ->leftJoin('ref_prop_ownership_types as o', 'o.id', '=', 'pp.ownership_type_mstr_id')
            ->leftJoin('ref_prop_types as p', 'p.id', '=', 'pp.prop_type_mstr_id')
            ->leftJoin('ref_prop_road_types as r', 'r.id', '=', 'pp.road_type_mstr_id')
            ->where('h.id', $id)
            ->first();
    }

    /**
     * | Get Harvesting By Harvesting No
     */
    public function getDtlsByHarvestingNo($harvestingNo)
    {
        return DB::table('prop_active_harvestings as h')
            ->select(
                'h.id',
                'h.application_no',
                'p.new_holding_no',
                'p.id as property_id',
                'p.ward_mstr_id',
                'p.new_ward_mstr_id',
                'u.ward_name as old_ward_no',
                'u1.ward_name as new_ward_no'
            )
            ->join('prop_properties as p', 'p.id', '=', 'h.property_id')
            ->join('ulb_ward_masters as u', 'p.ward_mstr_id', '=', 'u.id')
            ->leftJoin('ulb_ward_masters as u1', 'p.new_ward_mstr_id', '=', 'u1.id')
            ->where('application_no', $harvestingNo)
            ->first();
    }

    /**
     * 
     */
    public function getHarvestingNo($appId)
    {
        return PropActiveHarvesting::select('*')
            ->where('id', $appId)
            ->first();
    }

    /**
     * 
     */
    public function harvestingNo($id)
    {
        $count = PropActiveHarvesting::where('id', $id)
            ->select('id')
            ->get();
        $harvestingNo = 'HAR' . "/" . str_pad($count['0']->id, 5, '0', STR_PAD_LEFT);

        return $harvestingNo;
    }
}
