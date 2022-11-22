<?php

namespace App\Traits\Property;

use App\Models\Property\ActiveSaf;
use App\Models\Property\PropActiveSaf;
use App\Models\UlbWardMaster;
use Carbon\Carbon;
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
    public function tApplySaf($saf, $req, $safNo, $assessmentTypeId)
    {
        $saf->has_previous_holding_no = $req->hasPreviousHoldingNo;
        $saf->previous_holding_id = $req->previousHoldingId;
        $saf->previous_ward_mstr_id = $req->previousWard;
        $saf->is_owner_changed = $req->isOwnerChanged;
        $saf->transfer_mode_mstr_id = $req->transferModeId;
        $saf->saf_no = $safNo;
        $saf->holding_no = $req->holdingNo;
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
        $saf->road_type_mstr_id = $req->roadType;
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
        $saf->tower_area = $req->towerArea;
        $saf->tower_installation_date = $req->towerInstallationDate;
        $saf->is_hoarding_board = $req->isHoardingBoard;
        $saf->hoarding_area = $req->hoardingArea;
        $saf->hoarding_installation_date = $req->hoardingInstallationDate;
        $saf->is_petrol_pump = $req->isPetrolPump;
        $saf->under_ground_area = $req->undergroundArea;
        $saf->petrol_pump_completion_date = $req->petrolPumpCompletionDate;
        $saf->is_water_harvesting = $req->isWaterHarvesting;
        $saf->land_occupation_date = $req->landOccupationDate;
        $saf->payment_status = $req->paymentStatus;
        $saf->doc_verify_status = $req->docVerifyStatus;
        $saf->doc_verify_cancel_remarks = $req->docVerifyCancelRemark;

        $saf->application_date =  Carbon::now()->format('Y-m-d');
        $saf->saf_pending_status = $req->safPendingStatus;
        $saf->assessment_type = $assessmentTypeId;
        $saf->doc_upload_status = $req->docUploadStatus;
        $saf->saf_distributed_dtl_id = $req->safDistributedDtl;
        $saf->prop_dtl_id = $req->propDtl;
        $saf->prop_state = $req->propState;
        $saf->corr_state = $req->corrState;
        $saf->holding_type = $req->holdingType;
        $saf->ip_address = $req->ipAddress;
        $saf->property_assessment_id = $assessmentTypeId;
        $saf->new_ward_mstr_id = $req->newWard;
        $saf->percentage_of_property_transfer = $req->percOfPropertyTransfer;
        $saf->apartment_details_id = $req->apartmentDetail;
    }

    // Trait SAF Owner
    public function tApplySafOwner($owner, $saf, $owner_details)
    {
        $owner->saf_id = $saf->id;
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
    public function tApplySafFloor($floor, $saf, $floor_details)
    {
        $floor->saf_id = $saf->id;
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
    public function getSaf()
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
                'prop_active_safs.assessment_type'
            );
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
        $array['propertyType'] = $req['property_type'];
        $array['dateOfPurchase'] = $req['ward_mstr_id'];
        $array['ownershipType'] = $req['ownership_type_mstr_id'];
        $array['roadType'] = $req['road_type_mstr_id'];
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
}
