<?php

namespace App\Traits\Property;

use App\Models\Property\ActiveSaf;
use App\Models\Property\PropActiveSaf;
use App\Models\UlbWardMaster;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * | Created On-17-10-2022 
 * | Created By - Anshu Kumar
 * | Created for - Code Reausable for SAF Repository
 */

trait SAF
{
    /**
     * | Apply SAF Trait
     */
    public function tApplySaf($saf, $req, $safNo, $roadWidthType)
    {
        $saf->has_previous_holding_no = $req->hasPreviousHoldingNo;
        $saf->previous_holding_id = $req->previousHoldingId;
        $saf->previous_ward_mstr_id = $req->previousWard;
        $saf->is_owner_changed = $req->isOwnerChanged;
        $saf->transfer_mode_mstr_id = $req->transferModeId;
        $saf->saf_no = $safNo;
        $saf->ward_mstr_id = $req->ward;
        $saf->ownership_type_mstr_id = $req->ownershipType;
        $saf->prop_type_mstr_id = $req->propertyType;
        $saf->appartment_name = $req->apartmentName;
        $saf->flat_registry_date = $req->flatRegistryDate;
        $saf->zone_mstr_id = $req->zone;
        $saf->no_electric_connection = $req->electricityConnection;
        $saf->elect_consumer_no = $req->electricityCustNo;
        $saf->elect_acc_no = $req->electricityAccNo;
        $saf->elect_bind_book_no = $req->electricityBindBookNo;
        $saf->elect_cons_category = $req->electricityConsCategory;
        $saf->building_plan_approval_no = $req->buildingPlanApprovalNo;
        $saf->building_plan_approval_date = $req->buildingPlanApprovalDate;
        $saf->water_conn_no = $req->waterConnNo;
        $saf->water_conn_date = $req->waterConnDate;
        $saf->khata_no = $req->khataNo;
        $saf->plot_no = $req->plotNo;
        $saf->village_mauja_name = $req->villageMaujaName;
        $saf->road_type_mstr_id = $roadWidthType;
        $saf->area_of_plot = $req->areaOfPlot;
        $saf->prop_address = $req->propAddress;
        $saf->prop_city = $req->propCity;
        $saf->prop_dist = $req->propDist;
        $saf->prop_pin_code = $req->propPinCode;
        $saf->is_corr_add_differ = $req->isCorrAddDiffer;
        $saf->corr_address = $req->corrAddress;
        $saf->corr_city = $req->corrCity;
        $saf->corr_dist = $req->corrDist;
        $saf->corr_pin_code = $req->corrPinCode;

        $saf->is_mobile_tower = $req->isMobileTower;
        if ($req->isMobileTower == 1) {
            $saf->tower_area = $req->mobileTower['area'];
            $saf->tower_installation_date = $req->mobileTower['dateFrom'];
        }

        $saf->is_hoarding_board = $req->isHoardingBoard;
        if ($req->isHoardingBoard == 1) {
            $saf->hoarding_area = $req->hoardingBoard['area'];
            $saf->hoarding_installation_date = $req->hoardingBoard['dateFrom'];
        }

        $saf->is_petrol_pump = $req->isPetrolPump;
        if ($req->isPetrolPump == 1) {
            $saf->under_ground_area = $req->petrolPump['area'];
            $saf->petrol_pump_completion_date = $req->petrolPump['dateFrom'];
        }

        $saf->is_water_harvesting = $req->isWaterHarvesting;
        $saf->land_occupation_date = $req->landOccupationDate;
        $saf->doc_verify_cancel_remarks = $req->docVerifyCancelRemark;

        $saf->application_date =  Carbon::now()->format('Y-m-d');
        $saf->assessment_type = $req->assessmentType;
        $saf->saf_distributed_dtl_id = $req->safDistributedDtl;
        $saf->prop_dtl_id = $req->propDtl;
        $saf->prop_state = $req->propState;
        $saf->corr_state = $req->corrState;
        $saf->holding_type = $req->holdingType;
        $saf->ip_address = $req->ipAddress;
        // $saf->property_assessment_id = $req->assessmentType;
        $saf->new_ward_mstr_id = $req->newWard;
        $saf->percentage_of_property_transfer = $req->percOfPropertyTransfer;
        $saf->apartment_details_id = $req->apartmentDetail;
        $saf->applicant_name = collect($req->owner)->first()['ownerName'];
        $saf->road_width = $req->roadType;
    }

    // Trait SAF Owner
    public function tApplySafOwner($owner, $safId, $owner_details)
    {
        $owner->saf_id = $safId;
        $owner->owner_name = $owner_details['ownerName'] ?? null;
        $owner->guardian_name = $owner_details['guardianName'] ?? null;
        $owner->relation_type = $owner_details['relation'] ?? null;
        $owner->mobile_no = $owner_details['mobileNo'] ?? null;
        $owner->email = $owner_details['email'] ?? null;
        $owner->pan_no = $owner_details['pan'] ?? null;
        $owner->aadhar_no = $owner_details['aadhar'] ?? null;
        $owner->gender = $owner_details['gender'] ?? null;
        $owner->dob = $owner_details['dob'] ?? null;
        $owner->is_armed_force = $owner_details['isArmedForce'] ?? null;
        $owner->is_specially_abled = $owner_details['isSpeciallyAbled'] ?? null;
    }

    // Trait SAF Floors
    public function tApplySafFloor($floor, $safId, $floor_details)
    {
        $floor->saf_id = $safId;
        $floor->floor_mstr_id = $floor_details['floorNo'] ?? null;
        $floor->usage_type_mstr_id = $floor_details['useType'] ?? null;
        $floor->const_type_mstr_id = $floor_details['constructionType'] ?? null;
        $floor->occupancy_type_mstr_id = $floor_details['occupancyType'] ?? null;
        $floor->builtup_area = $floor_details['buildupArea'] ?? null;
        $floor->date_from = $floor_details['dateFrom'] ?? null;
        $floor->date_upto = $floor_details['dateUpto'] ?? null;
        $floor->prop_floor_details_id = $floor_details['propFloorDetail'] ?? null;
    }

    // SAF Inbox 
    public function getSaf($workflowIds)
    {
        $data = DB::table('prop_active_safs')
            ->join('prop_active_safs_owners as o', 'o.saf_id', '=', 'prop_active_safs.id')
            ->join('ref_prop_types as p', 'p.id', '=', 'prop_active_safs.prop_type_mstr_id')
            ->join('ulb_ward_masters as ward', 'ward.id', '=', 'prop_active_safs.ward_mstr_id')
            ->select(
                'prop_active_safs.saf_no',
                'prop_active_safs.id',
                'prop_active_safs.ward_mstr_id',
                'ward.ward_name as ward_no',
                'prop_active_safs.prop_type_mstr_id',
                'prop_active_safs.appartment_name',
                DB::raw("string_agg(o.id::VARCHAR,',') as owner_id"),
                DB::raw("string_agg(o.owner_name,',') as owner_name"),
                'p.property_type',
                'prop_active_safs.assessment_type as assessment',
                'prop_active_safs.application_date as apply_date',
                'prop_active_safs.parked'
            )
            ->whereIn('workflow_id', $workflowIds);
        return $data;
    }

    /**
     * | Generate SAF No
     */
    /**
     * desc This function return the safNo of the application
     * format: SAF/application_type/ward_no/count active application on the basise of ward_id
     *         3 |       02       |   03   |            05    ;
     * request : ward_id,assessment_type,ulb_id;
     * #==========================================
     * --------Tables------------
     * activ_saf_details  -> for counting;
     * ward_matrs   -> for ward_no;
     * ===========================================
     * #count <- count(activ_saf_details.*)
     * #ward_no <- ward_matrs.ward_no
     * #safNo <- "SAF/".str_pad($assessment_type,2,'0',STR_PAD_LEFT)."/".str_pad($word_no,3,'0',STR_PAD_LEFT)."/".str_pad($count,5,'0',STR_PAD_LEFT)
     * Status-Closed
     */
    public function safNo($ward_id, $assessment_type, $ulb_id)
    {
        $count = PropActiveSaf::where('ward_mstr_id', $ward_id)
            ->where('ulb_id', $ulb_id)
            ->count() + 1;
        $ward_no = UlbWardMaster::select("ward_name")->where('id', $ward_id)->first()->ward_name;
        return $safNo = "SAF/" . str_pad($assessment_type, 2, '0', STR_PAD_LEFT) . "/" . str_pad($ward_no, 3, '0', STR_PAD_LEFT) . "/" . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    /**
     * | Get SAF Request Details for SAF Calculation by SAF ID
     */

    public function generateSafRequest($req)
    {
        $array = array();

        $array['ward'] = $req['ward_mstr_id'];
        $array['propertyType'] = $req['prop_type_mstr_id'];
        $array['dateOfPurchase'] = $req['ward_mstr_id'];
        $array['ownershipType'] = $req['ownership_type_mstr_id'];
        $array['roadType'] = $req['road_width'];
        $array['areaOfPlot'] = $req['area_of_plot'];
        $array['isMobileTower'] = $req['is_mobile_tower'];
        $array['mobileTower']['area'] = $req['tower_area'];
        $array['mobileTower']['dateFrom'] = $req['tower_installation_date'];
        $array['isHoardingBoard'] = $req['is_hoarding_board'];
        $array['hoardingBoard']['area'] = $req['hoarding_area'];
        $array['hoardingBoard']['dateFrom'] = $req['hoarding_installation_date'];
        $array['isPetrolPump'] = $req['is_petrol_pump'];
        $array['petrolPump']['area'] = $req['under_ground_area'];
        $array['petrolPump']['dateFrom'] = $req['petrol_pump_completion_date'];
        $array['isWaterHarvesting'] = $req['is_water_harvesting'];
        $array['zone'] = $req['zone_mstr_id'];
        $refFloors = $req['floors'];

        foreach ($refFloors as $key => $refFloor) {
            $array['floor'][$key]['floorNo'] = $refFloor['floor_mstr_id'];
            $array['floor'][$key]['useType'] = $refFloor['usage_type_mstr_id'];
            $array['floor'][$key]['constructionType'] = $refFloor['const_type_mstr_id'];
            $array['floor'][$key]['occupancyType'] = $refFloor['occupancy_type_mstr_id'];
            $array['floor'][$key]['buildupArea'] = $refFloor['builtup_area'];
            $array['floor'][$key]['dateFrom'] = $refFloor['date_from'];
            $array['floor'][$key]['dateUpto'] = $refFloor['date_upto'];
        }

        $refFloors = $req['owners'];

        foreach ($refFloors as $key => $refFloor) {
            $array['owner'][$key]['ownerName'] = $refFloor['owner_name'];
            $array['owner'][$key]['gender'] = $refFloor['gender'];
            $array['owner'][$key]['guardianName'] = $refFloor['guardian_name'];
            $array['owner'][$key]['relation'] = $refFloor['relation_type'];
            $array['owner'][$key]['mobileNo'] = $refFloor['mobile_no'];
            $array['owner'][$key]['email'] = $refFloor['email'];
            $array['owner'][$key]['aadhar'] = $refFloor['aadhar_no'];
            $array['owner'][$key]['isArmedForce'] = $refFloor['is_armed_force'];
            $array['owner'][$key]['isSpeciallyAbled'] = $refFloor['is_specially_abled'];
            $array['owner'][$key]['dob'] = $refFloor['dob'];
        }

        return $array;
    }

    // Assessment Type 
    public function readAssessmentType($safInbox)
    {
        $GRID = collect($safInbox)->map(function ($value) {
            switch ($value->assessment_type) {
                case 1;
                    $value->assessment_type = Config::get('PropertyConstaint.ASSESSMENT-TYPE.1');
                    break;
                case 2;
                    $value->assessment_type = Config::get('PropertyConstaint.ASSESSMENT-TYPE.2');
                    break;
                case 3;
                    $value->assessment_type = Config::get('PropertyConstaint.ASSESSMENT-TYPE.3');
                    break;
            }
            return $value;
        });
        return $GRID;
    }

    /**
     * | Generated SAF Demand to push the value in propSafsDemand Table 
     * | @param collection
     */
    public function generateSafDemand($collection)
    {
        $filtered = collect($collection)->map(function ($value) {
            return collect($value)->only([
                'qtr', 'holdingTax', 'waterTax', 'educationTax',
                'healthTax', 'latrineTax', 'quarterYear', 'dueDate', 'totalTax', 'arv', 'rwhPenalty', 'onePercPenaltyTax'
            ]);
        });

        $groupBy = $filtered->groupBy(['quarterYear', 'qtr']);

        $taxes = $groupBy->map(function ($values) {
            return $values->map(function ($collection) {
                return collect([
                    'quarterYear' => $collection->first()['quarterYear'],
                    'qtr' => $collection->first()['qtr'],
                    'dueDate' => $collection->first()['dueDate'],
                    'rwhPenalty' => $collection->sum('rwhPenalty'),
                    'onePercPenaltyTax' => roundFigure($collection->sum('onePercPenaltyTax')),
                    'arv' => $collection->sum('arv'),
                    'holdingTax' => $collection->sum('holdingTax'),
                    'waterTax' => $collection->sum('waterTax'),
                    'educationTax' => $collection->sum('educationTax'),
                    'healthCess' => $collection->sum('healthTax'),
                    'latrineTax' => $collection->sum('latrineTax'),
                    'additionTax' => $collection->sum('additionalTax'),
                    'latrineTax' => $collection->sum('latrineTax'),
                    'totalTax' => roundFigure($collection->sum('totalTax'))
                ]);
            });
        });

        return $taxes->values()->collapse();
    }

    /**
     * | Save SAF Demand
     */
    public function tSaveSafDemand($propSafDemand, $safDemandDetail)
    {
        $propSafDemand->water_tax = $safDemandDetail['waterTax'];
        $propSafDemand->education_cess = $safDemandDetail['educationTax'];
        $propSafDemand->health_cess = $safDemandDetail['healthCess'];
        $propSafDemand->latrine_tax = $safDemandDetail['latrineTax'];
        $propSafDemand->additional_tax = $safDemandDetail['additionTax'];
        $propSafDemand->holding_tax = $safDemandDetail['holdingTax'];
        $propSafDemand->amount = $safDemandDetail['totalTax'];
    }

    /**
     * | Get Active Saf Details
     */
    public function tActiveSafDetails()
    {
        return DB::table('prop_active_safs')
            ->select(
                'prop_active_safs.*',
                'prop_active_safs.assessment_type as assessment',
                'w.ward_name as old_ward_no',
                'w.ward_name as new_ward_no',
                'o.ownership_type',
                'p.property_type',
                'r.road_type as road_type_master',
                'wr.role_name as current_role_name'
            )
            ->join('ulb_ward_masters as w', 'w.id', '=', 'prop_active_safs.ward_mstr_id')
            ->join('wf_roles as wr', 'wr.id', '=', 'prop_active_safs.current_role')
            ->leftJoin('ulb_ward_masters as nw', 'nw.id', '=', 'prop_active_safs.new_ward_mstr_id')
            ->leftJoin('ref_prop_ownership_types as o', 'o.id', '=', 'prop_active_safs.ownership_type_mstr_id')
            ->leftJoin('ref_prop_types as p', 'p.id', '=', 'prop_active_safs.property_assessment_id')
            ->leftJoin('ref_prop_road_types as r', 'r.id', '=', 'prop_active_safs.road_type_mstr_id');
    }

    /**
     * | Trait to Save On Payment Property Rebates
     */
    public function tSavePropRebate($paymentPropRebate, $req, $rebate)
    {
        $paymentPropRebate->saf_id = $req->id;
        $paymentPropRebate->rebate_type_id = $rebate['rebateTypeId'];
        $paymentPropRebate->amount = $rebate['rebateAmount'];
        $paymentPropRebate->description = $rebate['rebateType'];
    }

    /**
     * | Trait to Save On Payment Property Penalties
     */
    public function tSavePropPenalties($paymentPropPenalty, $penaltyTypeId, $demandId, $amount)
    {
        $paymentPropPenalty->saf_demand_id = $demandId;
        $paymentPropPenalty->penalty_type_id = $penaltyTypeId;
        $paymentPropPenalty->amount = $amount;
        $paymentPropPenalty->penalty_date = Carbon::now()->format('Y-m-d');
    }

    /**
     * | to Get Property Details
     */
    public function tPropertyDetails()
    {
        return DB::table('prop_properties')
            ->select('s.*', 's.assessment_type as assessment', 'w.ward_name as old_ward_no', 'o.ownership_type', 'p.property_type')
            ->join('prop_safs as s', 's.id', '=', 'prop_properties.saf_id')
            ->join('ulb_ward_masters as w', 'w.id', '=', 's.ward_mstr_id')
            ->leftJoin('ulb_ward_masters as nw', 'nw.id', '=', 's.new_ward_mstr_id')
            ->join('ref_prop_ownership_types as o', 'o.id', '=', 's.ownership_type_mstr_id')
            ->leftJoin('ref_prop_types as p', 'p.id', '=', 's.property_assessment_id')
            ->where('prop_properties.status', 1);
    }
}
