<?php

namespace App\Repository\SAF;

use App\Repository\SAF\SafRepository;
use Illuminate\Http\Request;
use App\Models\ActiveSafDetail;
use App\Models\ActiveSafFloorDetail;
use App\Models\ActiveSafOwnerDetail;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * | Created On-10-08-2022
 * | Created By-Anshu Kumar
 * -----------------------------------------------------------------------------------------
 * | SAF Module all operations 
 */
class EloquentSafRepository implements SafRepository
{
    /**
     * | Citizens Applying For SAF
     * | Proper Validation will be applied after 
     * | @param Illuminate\Http\Request
     * | @param Request $request
     * | @param response
     */
    public function applySaf(Request $request)
    {
        // dd($request->all());
        DB::beginTransaction();
        try {
            $saf = new ActiveSafDetail;
            $saf->has_previous_holding_no = $request->hasPreviousHoldingNo;
            $saf->previous_holding_id = $request->previousHoldingId;
            $saf->previous_ward_mstr_id = $request->previousWard;
            $saf->is_owner_changed = $request->isOwnerChanged;
            $saf->transfer_mode_mstr_id = $request->transferMode;
            $saf->saf_no = $request->safNo;
            $saf->holding_no = $request->holdingNo;
            $saf->ward_mstr_id = $request->ward;
            $saf->ownership_type_mstr_id = $request->ownershipType;
            $saf->prop_type_mstr_id = $request->propertyType;
            $saf->appartment_name = $request->apartmentName;
            $saf->flat_registry_date = $request->flatRegistryDate;
            $saf->zone_mstr_id = $request->zone;
            $saf->no_electric_connection = $request->electricityConnection;
            $saf->elect_consumer_no = $request->electricityCustNo;
            $saf->elect_acc_no = $request->electricityAccNo;
            $saf->elect_bind_book_no = $request->electricityBindBookNo;
            $saf->elect_cons_category = $request->electricityConsCategory;
            $saf->building_plan_approval_no = $request->buildingPlanApprovalNo;
            $saf->building_plan_approval_date = $request->buildingPlanApprovalDate;
            $saf->water_conn_no = $request->waterConnNo;
            $saf->water_conn_date = $request->waterConnDate;
            $saf->khata_no = $request->khataNo;
            $saf->plot_no = $request->plotNo;
            $saf->village_mauja_name = $request->villageMaujaName;
            $saf->road_type_mstr_id = $request->roadType;
            $saf->area_of_plot = $request->areaOfPlot;
            $saf->prop_address = $request->propAddress;
            $saf->prop_city = $request->propCity;
            $saf->prop_dist = $request->propDist;
            $saf->prop_pin_code = $request->propPinCode;
            $saf->is_corr_add_differ = $request->isCorrAddDiffer;
            $saf->corr_address = $request->corrAddress;
            $saf->corr_city = $request->corrCity;
            $saf->corr_dist = $request->corrDist;
            $saf->corr_pin_code = $request->corrPinCode;
            $saf->is_mobile_tower = $request->isMobileTower;
            $saf->tower_area = $request->towerArea;
            $saf->tower_installation_date = $request->towerInstallationDate;
            $saf->is_hoarding_board = $request->isHoardingBoard;
            $saf->hoarding_area = $request->hoardingArea;
            $saf->hoarding_installation_date = $request->hoardingInstallationDate;
            $saf->is_petrol_pump = $request->isPetrolPump;
            $saf->under_ground_area = $request->undergroundArea;
            $saf->petrol_pump_completion_date = $request->petrolPumpCompletionDate;
            $saf->is_water_harvesting = $request->isWaterHarvesting;
            $saf->land_occupation_date = $request->landOccupationDate;
            $saf->payment_status = $request->paymentStatus;
            $saf->doc_verify_status = $request->docVerifyStatus;
            $saf->doc_verify_date = $request->docVerifyDate;
            $saf->doc_verify_emp_details_id = $request->docVerifyEmpDetail;
            $saf->doc_verify_cancel_remarks = $request->docVerifyCancelRemark;
            $saf->field_verify_status = $request->fieldVerifyStatus;
            $saf->field_verify_date = $request->fieldVerifyDate;
            $saf->field_verify_emp_details_id = $request->fieldVerifyEmpDetail;
            $saf->emp_details_id = $request->empDetails;
            // $saf->status = $request->status;
            $saf->apply_date = $request->applyDate;
            $saf->saf_pending_status = $request->safPendingStatus;
            $saf->assessment_type = $request->assessmentType;
            $saf->doc_upload_status = $request->docUploadStatus;
            $saf->saf_distributed_dtl_id = $request->safDistributedDtl;
            $saf->prop_dtl_id = $request->propDtl;
            $saf->prop_state = $request->propState;
            $saf->corr_state = $request->corrState;
            $saf->holding_type = $request->holdingType;
            $saf->ip_address = $request->ipAddress;
            $saf->property_assessment_id = $request->propertyAssessment;
            $saf->new_ward_mstr_id = $request->newWard;
            $saf->percentage_of_property_transfer = $request->percOfPropertyTransfer;
            $saf->apartment_details_id = $request->apartmentDetail;
            $saf->ulb_id = auth()->user()->ulb_id;
            $saf->save();

            // SAF Owner Details
            if ($request['owner']) {
                $owner_detail = $request['owner'];
                foreach ($owner_detail as $owner_details) {
                    $owner = new ActiveSafOwnerDetail;
                    $owner->saf_dtl_id = $saf->id;
                    $owner->owner_name = $owner_details['ownerName'];
                    $owner->guardian_name = $owner_details['guardianName'];
                    $owner->relation_type = $owner_details['relation'];
                    $owner->mobile_no = $owner_details['mobileNo'];
                    $owner->email = $owner_details['email'];
                    $owner->pan_no = $owner_details['pan'];
                    $owner->aadhar_no = $owner_details['aadhar'];
                    $owner->emp_details_id = $owner_details['empDetail'];
                    $owner->rmc_saf_owner_dtl_id = $owner_details['rmcSafOwnerDtl'];
                    $owner->rmc_saf_dtl_id = $owner_details['rmcSafDetail'];
                    $owner->gender = $owner_details['gender'];
                    $owner->dob = $owner_details['dob'];
                    $owner->is_armed_force = $owner_details['isArmedForce'];
                    $owner->is_specially_abled = $owner_details['isSpeciallyAbled'];
                    $owner->save();
                }
            }

            // Floor Details
            if ($request['floor']) {
                $floor_detail = $request['floor'];
                foreach ($floor_detail as $floor_details) {
                    $floor = new ActiveSafFloorDetail();
                    $floor->saf_dtl_id = $saf->id;
                    $floor->floor_mstr_id = $floor_details['floorNo'];
                    $floor->usage_type_mstr_id = $floor_details['useType'];
                    $floor->const_type_mstr_id = $floor_details['constructionType'];
                    $floor->occupancy_type_mstr_id = $floor_details['occupancyType'];
                    $floor->builtup_area = $floor_details['buildupArea'];
                    $floor->date_from = $floor_details['dateFrom'];
                    $floor->date_upto = $floor_details['dateUpto'];
                    $floor->prop_floor_details_id = $floor_details['propFloorDetail'];
                    $floor->save();
                }
            }

            DB::commit();
            return response()->json('Successfully Submitted Your Application', 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json($e, 400);
        }
    }
}
