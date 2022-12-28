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
            'a.applicant_name',
            'a.ward_mstr_id',
            'u.ward_name as ward_no',
            'a.holding_no',
            'a.prop_type_mstr_id',
            'p.property_type',
            'prop_active_harvestings.workflow_id',
            'prop_active_harvestings.current_role as role_id'
        )
            ->join('prop_properties as a', 'a.id', '=', 'prop_active_harvestings.property_id')
            ->join('ref_prop_types as p', 'p.id', '=', 'a.prop_type_mstr_id')
            ->join('ulb_ward_masters as u', 'u.id', '=', 'a.ward_mstr_id')
            ->where('prop_active_harvestings.status', 1)
            ->where('prop_active_harvestings.ulb_id', $ulbId);
    }

    public function saves($request, $ulbWorkflowId, $initiatorRoleId, $finisherRoleId, $applicationNo)
    {
        $userId = auth()->user()->id;
        $ulbId = auth()->user()->ulb_id;

        $waterHaravesting = new PropActiveHarvesting();
        $waterHaravesting->property_id = $request->propertyId;
        $waterHaravesting->harvesting_status = $request->isWaterHarvestingBefore;
        $waterHaravesting->date_of_completion  =  $request->dateOfCompletion;
        $waterHaravesting->workflow_id = $ulbWorkflowId->id;
        $waterHaravesting->current_role = collect($initiatorRoleId)->first()->role_id;
        $waterHaravesting->initiator_role_id = collect($initiatorRoleId)->first()->role_id;
        $waterHaravesting->finisher_role_id = collect($finisherRoleId)->first()->role_id;
        $waterHaravesting->user_id = $userId;
        $waterHaravesting->ulb_id = $ulbId;
        $waterHaravesting->application_no = $applicationNo;
        $waterHaravesting->save();

        return $waterHaravesting->id;
    }
}
