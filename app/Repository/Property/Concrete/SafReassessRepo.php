<?php

namespace App\Repository\Property\Concrete;

use App\EloquentClass\Property\SafCalculation;
use App\Models\Property\ActiveSaf;
use App\Models\Property\ActiveSafsFloorDtls;
use App\Models\Property\ActiveSafsOwnerDtl;
use App\Models\Property\PropLevelPending;
use App\Models\Workflows\WfWorkflow;
use App\Repository\Property\Interfaces\iSafReassessRepo;
use App\Traits\Property\SAF;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * | Created On - 17-11-2022
 * | Created By - Anshu Kumar
 * | Property SAF Reassessment Repository
 */

class SafReassessRepo implements iSafReassessRepo
{
    use SAF;
    use Workflow;
    /**
     * | Apply for Reassessment
     */
    public function applyReassessment($req)
    {
        $user_id = auth()->user()->id;
        $ulb_id = auth()->user()->ulb_id;

        try {
            $workflow_id = Config::get('workflow-constants.SAF_WORKFLOW_ID');
            $ulbWorkflowId = WfWorkflow::where('wf_master_id', $workflow_id)
                ->where('ulb_id', $ulb_id)
                ->first();


            if (!in_array($req->assessmentType, ["NewAssessment", "New Assessment", "ReAssessment", "Mutation"])) {
                return responseMsg(false, "Invalid Assessment Type", "");
            }

            $rules = [];

            if (in_array($req->assessmentType, ["ReAssessment", "Mutation"])) {
                $rules["previousHoldingId"] = "required";
                $message["previousHoldingId.required"] = "Old Property Id Requird";
                $rules["holdingNo"] = "required";
                $message["holdingNo.required"] = "holding No. Is Requird";
            }

            $req->assessmentType = $req->assessmentType == "NewAssessment" ? "New Assessment" : $req->assessmentType;
            if ($req->roadType <= 0)
                $req->roadType = 4;
            elseif ($req->roadType > 0 && $req->roadType < 20)
                $req->roadType = 3;
            elseif ($req->roadType >= 20 && $req->roadType <= 39)
                $req->roadType = 2;
            elseif ($req->roadType > 40)
                $req->roadType = 1;

            $safCalculation = new SafCalculation();
            $safTaxes = $safCalculation->calculateTax($req);

            DB::beginTransaction();
            $assessmentTypeId = Config::get("PropertyConstaint.ASSESSMENT-TYPE." . $req->assessmentType);
            // dd($req->ward);
            $safNo = $this->safNo($req->ward, $assessmentTypeId, $ulb_id);
            $saf = new ActiveSaf();
            $saf->has_previous_holding_no = $req->hasPreviousHoldingNo;
            $saf->previous_holding_id = $req->previousHoldingId;
            $saf->previous_ward_mstr_id = $req->previousWard;
            $saf->is_owner_changed = $req->isOwnerChanged;
            $saf->transfer_mode_mstr_id = $req->transferMode;
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
            $saf->assessment_type = $req->assessmentType;
            $saf->doc_upload_status = $req->docUploadStatus;
            $saf->saf_distributed_dtl_id = $req->safDistributedDtl;
            $saf->prop_dtl_id = $req->propDtl;
            $saf->prop_state = $req->propState;
            $saf->corr_state = $req->corrState;
            $saf->holding_type = $req->holdingType;
            $saf->ip_address = $req->ipAddress;
            $saf->property_assessment_id = $req->propertyAssessment;
            $saf->new_ward_mstr_id = $req->newWard;
            $saf->percentage_of_property_transfer = $req->percOfPropertyTransfer;
            $saf->apartment_details_id = $req->apartmentDetail;
            // workflows
            $saf->user_id = $user_id;
            // $saf->current_role = $workflows->initiator;
            $saf->workflow_id = $ulbWorkflowId->id;
            $saf->ulb_id = $ulb_id;
            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);                // Get Current Initiator ID
            $initiatorRoleId = DB::select($refInitiatorRoleId);
            $saf->current_role = $initiatorRoleId[0]->role_id;
            $saf->save();

            // SAF Owner Details
            if ($req['owner']) {
                $owner_detail = $req['owner'];
                foreach ($owner_detail as $owner_details) {
                    $owner = new ActiveSafsOwnerDtl();
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
                    $owner->save();
                }
            }

            // Floor Details
            if ($req['floor']) {
                $floor_detail = $req['floor'];
                foreach ($floor_detail as $floor_details) {
                    $floor = new ActiveSafsFloorDtls();
                    $floor->saf_id = $saf->id;
                    $floor->floor_mstr_id = $floor_details['floorNo'] ?? null;
                    $floor->usage_type_mstr_id = $floor_details['useType'] ?? null;
                    $floor->const_type_mstr_id = $floor_details['constructionType'] ?? null;
                    $floor->occupancy_type_mstr_id = $floor_details['occupancyType'] ?? null;
                    $floor->builtup_area = $floor_details['buildupArea'] ?? null;
                    $floor->date_from = $floor_details['dateFrom'] ?? null;
                    $floor->date_upto = $floor_details['dateUpto'] ?? null;
                    $floor->prop_floor_details_id = $floor_details['propFloorDetail'] ?? null;
                    $floor->save();
                }
            }

            // Property SAF Label Pendings
            $refSenderRoleId = $this->getInitiatorId($ulbWorkflowId->id);
            $SenderRoleId = DB::select($refSenderRoleId);
            $labelPending = new PropLevelPending();
            $labelPending->saf_id = $saf->id;
            $labelPending->receiver_role_id = $SenderRoleId[0]->role_id;
            $labelPending->save();

            // Insert Tax
            // $tax = new InsertTax();
            // $tax->insertTax($saf->id, $user_id, $safTaxes);                                         // Insert SAF Tax

            DB::commit();
            return responseMsg(true, "Successfully Submitted Your Application Your SAF No. $safNo", ["safNo" => $safNo]);
        } catch (Exception $e) {
            DB::rollBack();
            return $e;
        }
    }
}
