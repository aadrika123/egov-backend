<?php

namespace App\Models\Property;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropActiveSaf extends Model
{
    use HasFactory;

    protected $guarded = [];
    // Store
    public function store($req)
    {
        $reqs = [
            'has_previous_holding_no' => $req->hasPreviousHoldingNo,
            'previous_holding_id' => $req->previousHoldingId,
            'previous_ward_mstr_id' => $req->previousWard,
            'is_owner_changed' => $req->isOwnerChanged,
            'transfer_mode_mstr_id' => $req->transferModeId,
            'saf_no' => $req->safNo,
            'holding_no' => $req->holdingNo,
            'ward_mstr_id' => $req->ward,
            'ownership_type_mstr_id' => $req->ownershipType,
            'prop_type_mstr_id' => $req->propertyType,
            'appartment_name' => $req->apartmentName,
            'flat_registry_date' => $req->flatRegistryDate,
            'zone_mstr_id' => $req->zone,
            'no_electric_connection' => $req->electricityConnection,
            'elect_consumer_no' => $req->electricityCustNo,
            'elect_acc_no' => $req->electricityAccNo,
            'elect_bind_book_no' => $req->electricityBindBookNo,
            'elect_cons_category' => $req->electricityConsCategory,
            'building_plan_approval_no' => $req->buildingPlanApprovalNo,
            'building_plan_approval_date' => $req->buildingPlanApprovalDate,
            'water_conn_no' => $req->waterConnNo,
            'water_conn_date' => $req->waterConnDate,
            'khata_no' => $req->khataNo,
            'plot_no' => $req->plotNo,
            'village_mauja_name' => $req->villageMaujaName,
            'road_type_mstr_id' => $req->roadWidthType,
            'area_of_plot' => $req->areaOfPlot,
            'prop_address' => $req->propAddress,
            'prop_city' => $req->propCity,
            'prop_dist' => $req->propDist,
            'prop_pin_code' => $req->propPinCode,
            'is_corr_add_differ' => $req->isCorrAddDiffer,
            'corr_address' => $req->corrAddress,
            'corr_city' => $req->corrCity,
            'corr_dist' => $req->corrDist,
            'corr_pin_code' => $req->corrPinCode,
            'is_mobile_tower' => $req->isMobileTower,
            'tower_area' => $req->mobileTower['area'],
            'tower_installation_date' => $req->mobileTower['dateFrom'],

            'is_hoarding_board' => $req->isHoardingBoard,
            'hoarding_area' => $req->hoardingBoard['area'],
            'hoarding_installation_date' => $req->hoardingBoard['dateFrom'],


            'is_petrol_pump' => $req->isPetrolPump,
            'under_ground_area' => $req->petrolPump['area'],
            'petrol_pump_completion_date' => $req->petrolPump['dateFrom'],

            'is_water_harvesting' => $req->isWaterHarvesting,
            'land_occupation_date' => $req->landOccupationDate,
            'doc_verify_cancel_remarks' => $req->docVerifyCancelRemark,
            'application_date' =>  Carbon::now()->format('Y-m-d'),
            'assessment_type' => $req->assessmentType,
            'saf_distributed_dtl_id' => $req->safDistributedDtl,
            'prop_dtl_id' => $req->propDtl,
            'prop_state' => $req->propState,
            'corr_state' => $req->corrState,
            'holding_type' => $req->holdingType,
            'ip_address' => getClientIpAddress(),
            'new_ward_mstr_id' => $req->newWard,
            'percentage_of_property_transfer' => $req->percOfPropertyTransfer,
            'apartment_details_id' => $req->apartmentDetail,
            'applicant_name' => collect($req->owner)->first()['ownerName'],
            'road_width' => $req->roadType,
            'user_id' => $req->userId,
            'workflow_id' => $req->workflowId,
            'ulb_id' => $req->ulbId,
            'current_role' => $req->initiatorRoleId,
            'initiator_role_id' => $req->initiatorRoleId,
            'finisher_role_id' => $req->finisherRoleId,
            'late_assess_penalty' => $req->lateAssessPenalty
        ];
        return PropActiveSaf::insertGetId($reqs);
    }

    // Update
    public function edit($req)
    {
        $saf = PropActiveSaf::find($req->id);

        $reqs = [
            'previous_ward_mstr_id' => $req->previousWard,
            'zone_mstr_id' => $req->zone,
            'no_electric_connection' => $req->electricityConnection,
            'elect_consumer_no' => $req->electricityCustNo,
            'elect_acc_no' => $req->electricityAccNo,
            'elect_bind_book_no' => $req->electricityBindBookNo,
            'elect_cons_category' => $req->electricityConsCategory,
            'building_plan_approval_no' => $req->buildingPlanApprovalNo,
            'building_plan_approval_date' => $req->buildingPlanApprovalDate,
            'water_conn_no' => $req->waterConnNo,
            'water_conn_date' => $req->waterConnDate,
            'khata_no' => $req->khataNo,
            'plot_no' => $req->plotNo,
            'village_mauja_name' => $req->villageMaujaName,
            'prop_address' => $req->propAddress,
            'prop_city' => $req->propCity,
            'prop_dist' => $req->propDist,
            'prop_pin_code' => $req->propPinCode,
            'is_corr_add_differ' => $req->isCorrAddDiffer,
            'corr_address' => $req->corrAddress,
            'corr_city' => $req->corrCity,
            'corr_dist' => $req->corrDist,
            'corr_pin_code' => $req->corrPinCode,

            'prop_state' => $req->propState,
            'corr_state' => $req->corrState,
            'new_ward_mstr_id' => $req->newWard
        ];

        return $saf->update($reqs);
    }

    /**
     * |-------------------------- safs list whose Holding are not craeted -----------------------------------------------|
     * | @var safDetails
     */
    public function allNonHoldingSaf()
    {
        try {
            $allSafList = PropActiveSaf::select(
                'id AS SafId'
            )
                ->get();
            return responseMsg(true, "Saf List!", $allSafList);
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }


    /**
     * |-------------------------- Details of the Mutation accordind to ID -----------------------------------------------|
     * | @param request
     * | @var mutation
     */
    public function allMutation($request)
    {
        $mutation = PropActiveSaf::where('id', $request->id)
            ->where('property_assessment_id', 3)
            ->get();
        return $mutation;
    }


    /**
     * |-------------------------- Details of the ReAssisments according to ID  -----------------------------------------------|
     * | @param request
     * | @var reAssisment
     */
    public function allReAssisment($request)
    {
        $reAssisment = PropActiveSaf::where('id', $request->id)
            ->where('property_assessment_id', 2)
            ->get();
        return $reAssisment;
    }


    /**
     * |-------------------------- Details of the NewAssisment according to ID  -----------------------------------------------|
     * | @var safDetails
     */
    public function allNewAssisment($request)
    {
        $newAssisment = PropActiveSaf::where('id', $request->id)
            ->where('property_assessment_id', 1)
            ->get();
        return $newAssisment;
    }


    /**
     * |-------------------------- safId According to saf no -----------------------------------------------|
     */
    public function getSafId($safNo)
    {
        return PropActiveSaf::where('saf_no', $safNo)
            ->select('id')
            ->get()
            ->first();
    }

    /**
     * | Get late Assessment by SAF id
     */
    public function getLateAssessBySafId($safId)
    {
        return PropActiveSaf::select('late_assess_penalty')
            ->where('id', $safId)
            ->first();
    }
}
