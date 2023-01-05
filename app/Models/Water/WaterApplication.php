<?php

namespace App\Models\Water;

use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropProperty;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterApplication extends Model
{
    use HasFactory;

    /**
     * |------------------------------------------ Save new water applications -----------------------------------------|
     * | @param
     * | 
        |
     */
    public function saveWaterApplication($req, $ulbWorkflowId, $initiatorRoleId, $finisherRoleId, $ulbId, $applicationNo, $waterFeeId)
    {
        $saveNewApplication = new WaterApplication();
        $saveNewApplication->connection_type_id     = $req->connectionTypeId;
        $saveNewApplication->property_type_id       = $req->propertyTypeId;
        $saveNewApplication->owner_type             = $req->ownerType;
        $saveNewApplication->category               = $req->category;
        $saveNewApplication->pipeline_type_id       = $req->pipelineTypeId;
        $saveNewApplication->ward_id                = $req->wardId;
        $saveNewApplication->area_sqft              = $req->areaSqft;
        $saveNewApplication->address                = $req->address;
        $saveNewApplication->landmark               = $req->landmark;
        $saveNewApplication->pin                    = $req->pin;
        $saveNewApplication->flat_count             = $req->flatCount;
        $saveNewApplication->elec_k_no              = $req->elecKNo;
        $saveNewApplication->elec_bind_book_no      = $req->elecBindBookNo;
        $saveNewApplication->elec_account_no        = $req->elecAccountNo;
        $saveNewApplication->elec_category          = $req->elecCategory;
        $saveNewApplication->connection_through     = $req->connection_through;
        $saveNewApplication->workflow_id            = $ulbWorkflowId->id;
        $saveNewApplication->connection_fee_id      = $waterFeeId;
        $saveNewApplication->current_role           = collect($initiatorRoleId)->first()->role_id;
        $saveNewApplication->initiator              = collect($initiatorRoleId)->first()->role_id;
        $saveNewApplication->finisher               = collect($finisherRoleId)->first()->role_id;
        $saveNewApplication->application_no         = $applicationNo;
        $saveNewApplication->ulb_id                 = $ulbId;
        $saveNewApplication->apply_date             = date('Y-m-d H:i:s');
        $saveNewApplication->user_id                = auth()->user()->id;

        # condition entry 
        if ($req->connection_through == 3) {
            $saveNewApplication->id_proof = 3;
        }
        if (!is_null($req->holdingNo)) {
            $propertyId = new PropProperty();
            $propertyId = $propertyId->getPropertyId($req->holdingNo);
            $saveNewApplication->prop_id = $propertyId->id;
            $saveNewApplication->holding_no = $req->holdingNo;
        }
        if (!is_null($req->saf_no)) {
            $safId = new PropActiveSaf();
            $safId = $safId->getSafId($req->saf_no);
            $saveNewApplication->saf_id = $safId->id;
            $saveNewApplication->saf_no = $req->saf_no;
        }

        $saveNewApplication->save();

        return $saveNewApplication->id;
    }


    /**
     * |----------------------- Get Water Application detals With all Relation ------------------|
     * | @param 
     */
    public function fullWaterDetails($request)
    {
        return  WaterApplication::join('wf_roles', 'wf_roles.id', '=', 'water_applications.current_role')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'water_applications.ward_id')
            ->join('water_connection_through_mstrs', 'water_connection_through_mstrs.id', '=', 'water_applications.connection_through')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'water_applications.ulb_id')
            ->join('water_connection_type_mstrs', 'water_connection_type_mstrs.id', '=', 'water_applications.connection_type_id')
            ->join('water_property_type_mstrs', 'water_property_type_mstrs.id', '=', 'water_applications.property_type_id')
            ->where('water_applications.id', $request->id)
            ->where('water_applications.status', 1);
    }
}
