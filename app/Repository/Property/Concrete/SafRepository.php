<?php

namespace App\Repository\Property\Concrete;

use App\Repository\Property\Interfaces\iSafRepository;
use Illuminate\Http\Request;
use App\Models\UlbWardMaster;

use App\Traits\Auth;
use App\Traits\Property\WardPermission;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\EloquentClass\Property\dSafCalculation;
use App\EloquentClass\Property\dPropertyTax;
use App\Models\Property\ActiveSaf;
use App\Models\Property\ActiveSafsFloorDtls;
use App\Models\Property\ActiveSafsOwnerDtl;
use App\Models\Property\PropLevelPending;
use App\Models\Property\PropMConstructionType;
use App\Models\Property\PropMFloor;
use App\Models\Property\PropMOccupancyType;
use App\Models\Property\PropMOwnershipType as PropertyPropMOwnershipType;
use App\Models\Property\PropMPropertyType;
use App\Models\Property\PropMUsageType;
use App\Models\Workflows\WfRole as WorkflowsWfRole;
use App\Models\Workflows\WfWorkflow;
use App\Models\WorkflowTrack;
use App\Traits\Workflow\Workflow as WorkflowTrait;
use App\Repository\Property\EloquentProperty;
use App\Traits\Property\SAF as GlobalSAF;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

/**
 * | Created On-10-08-2022
 * | Created By-Anshu Kumar
 * -----------------------------------------------------------------------------------------
 * | SAF Module all operations 
 */
class SafRepository implements iSafRepository
{
    use Auth;                                                               // Trait Used added by sandeep bara date 17-08-2022
    use WardPermission;
    use WorkflowTrait;
    use GlobalSAF;
    /**
     * | Citizens Applying For SAF
     * | Proper Validation will be applied after 
     * | @param Illuminate\Http\Request
     * | @param Request $request
     * | @param response
     */
    protected $property;
    protected $saf;
    protected $user_id;
    public function __construct()
    {
        $this->property = new EloquentProperty;
        $this->saf = new dSafCalculation();
        $this->propertyTax = new dPropertyTax();
    }

    /**
     * | Master data in Saf Apply
     * | @var ulbId Logged In User Ulb 
     */
    public function masterSaf()
    {
        $ulbId = auth()->user()->ulb_id;
        $wardMaster = UlbWardMaster::select('id', 'ward_name')
            ->where('ulb_id', $ulbId)
            ->get();
        $data = [];
        $data['ward_master'] = $wardMaster;
        $ownershipTypes = PropertyPropMOwnershipType::select('id', 'ownership_type')
            ->where('status', 1)
            ->get();
        $data['ownership_types'] = $ownershipTypes;
        $propertyType = PropMPropertyType::select('id', 'property_type')
            ->where('status', 1)
            ->get();
        $data['property_type'] = $propertyType;
        $floorType = PropMFloor::select('id', 'floor_name')
            ->where('status', 1)
            ->get();
        $data['floor_type'] = $floorType;
        $usageType = PropMUsageType::select('id', 'usage_type', 'usage_code')
            ->where('status', 1)
            ->get();
        $data['usage_type'] = $usageType;
        $occupancyType = PropMOccupancyType::select('id', 'occupancy_type')
            ->where('status', 1)
            ->get();
        $data['occupancy_type'] = $occupancyType;
        $constructionType = PropMConstructionType::select('id', "construction_type")
            ->where('status', 1)
            ->get();
        $data['construction_type'] = $constructionType;
        return  responseMsg(true, '', $data);
    }

    public function applySaf(Request $request)
    {
        $message = ["status" => false, "data" => $request->all(), "message" => ""];
        $user_id = auth()->user()->id;
        $isCitizen = auth()->user()->user_type == "Citizen" ? true : false;
        $ulb_id = auth()->user()->ulb_id;
        try {

            // Determining the initiator and finisher id
            $workflow_id = Config::get('workflow-constants.SAF_WORKFLOW_ID');
            $ulbWorkflowId = WfWorkflow::where('wf_master_id', $workflow_id)
                ->where('ulb_id', $ulb_id)
                ->first();


            if (!in_array($request->assessmentType, ["NewAssessment", "Reassessment", "Mutation"])) {
                return responseMsg(false, "Invalid Assessment Type", "");
            }
            $rules = [];
            if (in_array($request->assessmentType, ["Reassessment", "Mutation"])) {
                $rules["previousHoldingId"] = "required";
                $message["previousHoldingId.required"] = "Old Property Id Requird";
                $rules["holdingNo"] = "required";
                $message["holdingNo.required"] = "holding No. Is Requird";
            }
            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }

            $wardMaster = UlbWardMaster::select('id', 'ward_name')
                ->where('ulb_id', $ulb_id)
                ->get();

            $ward_no = array_filter(adjToArray($wardMaster), function ($val) {
                return $val['id'] == 111;
            });
            $ward_no = array_values($ward_no)[0]['ward_name'];

            // return $this->saf->buildingRulSet1(auth()->user()->ulb_id, 500, 1, 1, 1, 2, true, '1540-04-01');
            // return $this->saf->buildingRulSet2(auth()->user()->ulb_id, 500, 12, 2, 40, 1, '2020-04-01');
            // return $this->saf->buildingRulSet3(auth()->user()->ulb_id, 500, 12, 2, 19.9919, 1, true, 1, $ward_no, '2020-04-01');
            $inputs = $request->all();
            $inputs['ulb_id'] =  $ulb_id;
            $inputs['ward_no'] =  $ward_no;
            // $floorDetails = $this->saf->BuildingTax($inputs);                     // Get all The floor Details
            // //return $this->propertyTax->InsertTax(1, $this->saf->TotalTax);

            // // Late Assessment Penalty 
            // $demand = $this->saf->getLateAssessmentPenalty($inputs, $floorDetails);

            // // Total Rebates
            // $finalWithRebates = $this->saf->demandRebate($inputs, $demand);
            // // Final Payable Amount
            // $finalPayableAmount = $this->saf->payableAmount($finalWithRebates);
            // return $finalPayableAmount;

            // return ($this->saf->TotalTax);
            // $rules["ward"]="required|int";
            // $message["ward.required"]="Ward No. Required";
            // $message["ward.int"]="Ward ID Must Be Int Type";

            // $rules["ownershipType"] ="required|int";
            // $message["ownershipType.required"]="Ownership Type Is Required";
            // $message["ownershipType.int"]="Ownership Type ID Must Be Int Type";

            // $rules["propertyType"]  ="required|int";
            // $message["propertyType.required"]="Property Type Is Required";
            // $message["propertyType.int"]="Property Type ID Must Be Int Type";

            // $rules["roadType"]      ="required|numeric";
            // $message["roadType.required"]="Road Type Is Required";
            // $message["propertyType.numeric"]="Road Type Must Be Numeric Type";

            // $rules["areaOfPlot"]    ="required|numeric";
            // $message["areaOfPlot.required"]="AreaOfPlot Is Required";
            // $message["propertyType.numeric"]="AreaOfPlot Must Be Numeric Type";

            // $rules["isMobileTower"] ="required|bool";
            // $message["isMobileTower.required"]="isMobileTower Is Required";
            // $message["isMobileTower.bool"]="isMobileTower Must Be Boolean Type";

            // $rules["isHoardingBoard"]="required|bool";
            // $message["isHoardingBoard.required"]="isHoardingBoard Is Required";
            // $message["isHoardingBoard.bool"]="isHoardingBoard Must Be Boolean Type";

            // $rules["owner"]         ="required|array";
            // $message["owner.required"]= "Owner Required";

            // if(in_array($request->assessmentType,["Reassessment","Mutation"]))
            // {
            //     $rules["oldHoldingId"]="required";
            //     $message["oldHoldingId.required"]="Old Property Id Requird";                
            // }
            // if(in_array($request->assessmentType,["Mutation"]))
            // {
            //     $rules["transferMode"]="required";
            //     $message["transferMode.required"]="Transfer Mode Required";                
            // }
            // if(!in_array($request->propertyType,[4]))
            // {
            //     $rules["floor"]="required";
            //     $message["floor.required"]="Floor Is Required";

            //     if($request->propertyType==1)
            //     {
            //         $rules["isPetrolPump"]="required|bool";
            //         $message["isPetrolPump.required"]="isPetrolPump Is Required";
            //         $message["isPetrolPump.bool"]="isPetrolPump Is Boolian Type";
            //     }

            //     $rules["isWaterHarvesting"]="required|bool";   
            //     $message["isWaterHarvesting.required"]="isWaterHarvesting Is Required";   
            //     $message["isWaterHarvesting.bool"]="isWaterHarvesting Is Boolian Type";          
            // }
            // if($request->isPetrolPump)
            // {
            //     $rules["undergroundArea"]="required|numeric";
            //     $message["undergroundArea.required"]="Underground Area Is Required";

            //     $rules["petrolPumpCompletionDate"]="required|date";
            //     $message["petrolPumpCompletionDate.required"]="Petrol Pump Completion Date Is Required";
            // }
            // if($request->isHoardingBoard)
            // {
            //     $rules["hoardingArea"]="required|numeric";
            //     $message["hoardingArea.required"]="Hoarding Area Is Required";

            //     $rules["hoardingInstallationDate"]="required|date";
            //     $message["hoardingInstallationDate.required"]="Hoarding Installation Date Is Required";
            // }
            // if($request->isMobileTower)
            // {
            //     $rules["towerArea"]="required|numeric";
            //     $message["towerArea.required"]="Tower Area Is Required";

            //     $rules["towerInstallationDate"]="required|date";
            //     $message["towerInstallationDate.required"]="Tower Installation Date Is Required";
            // }
            // if($request->floor)
            // { 
            //     $rules["floor.*.floorNo"] = "required|int";
            //     $rules["floor.*.useType"] = "required|int";
            //     $rules["floor.*.constructionType"]="required|int";
            //     $rules["floor.*.occupancyType"]="required|int";
            //     $rules["floor.*.buildupArea"]="required|numeric";
            //     $rules["floor.*.dateFrom"]="required|date";
            //     $rules["floor.*.dateUpto"]="required|date";
            // }
            // if($request->owner)
            // { 
            //     #"/^([a-zA-Z]+)(\s[a-zA-Z]+)*$/" "[a-zA-Z0-9- ]+$/i"
            //     $rules["owner.*.ownerName"]="required|regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9]+)*$/";
            //     $rules["owner.*.guardianName"]="regex:/^([a-zA-Z]+)(\s[a-zA-Z]+)*$/";
            //     $rules["owner.*.relation"]="in:S/O,C/O,W/O,D/O,";
            //     $rules["owner.*.mobileNo"]="required|digits:10|regex:/[0-9]{10}/";
            //     $rules["owner.*.email"]="email";
            //     // $rules["owner.*.pan"]="required";
            //     $rules["owner.*.aadhar"]="digits:12|regex:/[0-9]{12}/";
            //     $rules["owner.*.isArmedForce"]="required|bool";
            //     $rules["owner.*.isSpeciallyAbled"]="required|bool";
            // }

            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $request->all(), $validator->errors());
            }

            $request->assessmentType = $request->assessmentType == "NewAssessment" ? "New Assessment" : $request->assessmentType;
            if ($request->roadType <= 0)
                $request->roadType = 4;
            elseif ($request->roadType > 0 && $request->roadType < 20)
                $request->roadType = 3;
            elseif ($request->roadType >= 20 && $request->roadType <= 39)
                $request->roadType = 2;
            elseif ($request->roadType > 40)
                $request->roadType = 1;
            DB::beginTransaction();
            $assessmentTypeId = Config::get("PropertyConstaint.ASSESSMENT-TYPE." . $request->assessmentType);
            // dd($request->ward);
            $safNo = $this->safNo($request->ward, $assessmentTypeId, $ulb_id);
            $saf = new ActiveSaf();
            $saf->has_previous_holding_no = $request->hasPreviousHoldingNo;
            $saf->previous_holding_id = $request->previousHoldingId;
            $saf->previous_ward_mstr_id = $request->previousWard;
            $saf->is_owner_changed = $request->isOwnerChanged;
            $saf->transfer_mode_mstr_id = $request->transferMode;
            $saf->saf_no = $safNo;
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
            $saf->doc_verify_cancel_remarks = $request->docVerifyCancelRemark;

            $saf->application_date =  Carbon::now()->format('Y-m-d');
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
            if ($request['owner']) {
                $owner_detail = $request['owner'];
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
            if ($request['floor']) {
                $floor_detail = $request['floor'];
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

            DB::commit();
            return responseMsg(true, "Successfully Submitted Your Application Your SAF No. $safNo", ["safNo" => $safNo]);
        } catch (Exception $e) {
            DB::rollBack();
            return $e;
        }
    }
    public function getPropIdByWardNoHodingNo(Request $request)
    {
        try {
            $rules = [
                "wardId" => "required",
                "holdingNo" => "required",
            ];
            $message = [
                "wardId.required" => "Ward id required",
                "holdingNo.required" => "Holding No required",
            ];
            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            } else {
                $inputs['ward_mstr_id'] = $request->ward_id;
                $inputs['holding_no'] = $request->holding_no;
                $data = $this->property->getPropIdByWardNoHodingNo($inputs);
                return responseMsg(true, '', $data,);
            }
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
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
     */
    public function safNo($ward_id, $assessment_type, $ulb_id)
    {
        $count = ActiveSaf::where('ward_mstr_id', $ward_id)
            ->where('ulb_id', $ulb_id)
            ->count() + 1;
        $ward_no = UlbWardMaster::select("ward_name")->where('id', $ward_id)->first()->ward_name;
        return $safNo = "SAF/" . str_pad($assessment_type, 2, '0', STR_PAD_LEFT) . "/" . str_pad($ward_no, 3, '0', STR_PAD_LEFT) . "/" . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    /**
     * ---------------------- Saf Workflow Inbox --------------------
     * | Initialization
     * -----------------
     * | @var userId > logged in user id
     * | @var ulbId > Logged In user ulb Id
     * | @var refWorkflowId > Workflow ID 
     * | @var workflowId > SAF Wf Workflow ID 
     * | @var query > Contains the Pg Sql query
     * | @var workflow > get the Data in laravel Collection
     * | @var checkDataExisting > check the fetched data collection in array
     * | @var roleId > Fetch all the Roles for the Logged In user
     * | @var data > all the Saf data of current logged roleid 
     * | @var occupiedWard > get all Permitted Ward Of current logged in user id
     * | @var wardId > filtered Ward Id from the data collection
     * | @var safInbox > Final returned Data
     * | @return response #safInbox
     * ---------------------------------------------------------------
     */
    #Inbox
    public function inbox()
    {
        $userId = auth()->user()->id;
        $ulbId = auth()->user()->ulb_id;
        $refWorkflowId = Config::get('workflow-constants.SAF_WORKFLOW_ID');
        $workflowId = WfWorkflow::where('wf_master_id', $refWorkflowId)
            ->where('ulb_id', $ulbId)
            ->first();
        try {
            $query = $this->getWorkflowInitiatorData($userId, $workflowId);                 // Trait get Workflow Initiator
            $workflow = collect(DB::select($query));

            $checkDataExisting = $workflow->toArray();


            // If the Current Role Is not a Initiator
            if (!$checkDataExisting) {
                $roles = $this->getRoleIdByUserId($userId);                                 // Trait get Role By User Id

                $roleId = $roles->map(function ($item, $key) {
                    return $item->wf_role_id;
                });

                $data = $this->getSaf()                                                     // Global SAF 
                    ->where('active_safs.ulb_id', $ulbId)
                    ->where('active_safs.status', 1)
                    ->whereIn('current_role', $roleId)
                    ->orderByDesc('id')
                    ->groupBy('active_safs.id', 'p.property_type', 'ward.ward_name')
                    ->get();

                $occupiedWard = $this->getWardByUserId($userId);                            // Get All Occupied Ward By user id

                $wardId = $occupiedWard->map(function ($item, $key) {
                    return $item->ward_id;
                });
                // return $wardId;
                $safInbox = $data->whereIn('ward_mstr_id', $wardId);
                return responseMsg(true, "Data Fetched", remove_null($safInbox));
            }

            // If current role Is a Initiator

            // Filteration only Ward id from workflow collection
            $wardId = $workflow->map(function ($item, $key) {
                return $item->ward_id;
            });

            $roles = $this->getRoleIdByUserId($userId);                                 // Trait get Role By User Id

            $roleId = $roles->map(function ($item, $key) {
                return $item->wf_role_id;
            });

            $safInbox = $this->getSaf()                                            // Global SAF 
                ->where('active_safs.ulb_id', $ulbId)
                ->where('active_safs.status', 1)
                ->whereIn('current_role', $roleId)
                ->whereIn('ward_mstr_id', $wardId)
                ->orderByDesc('id')
                ->groupBy('active_safs.id', 'p.property_type', 'ward.ward_name')
                ->get();

            return responseMsg(true, "Data Fetched", remove_null($safInbox));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Saf Outbox
     * | @var userId authenticated user id
     * | @var ulbId authenticated user Ulb Id
     * | @var workflowRoles get All Roles of the user id
     * | @var roles filteration of roleid from collections
     */
    #OutBox
    public function outbox()
    {
        try {
            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;
            $workflowRoles = $this->getRoleIdByUserId($userId);
            $roles = $workflowRoles->map(function ($value, $key) {
                return $value->wf_role_id;
            });

            $refWard = $this->getWardByUserId($userId);
            $wardId = $refWard->map(function ($value, $key) {
                return $value->ward_id;
            });

            $safData = $this->getSaf()
                ->whereNotIn('current_role', $roles)
                ->whereIn('ward_mstr_id', $wardId)
                ->orderByDesc('id')
                ->groupBy('active_safs.id', 'p.property_type', 'ward.ward_name')
                ->get();
            return responseMsg(true, "Data Fetched", remove_null($safData));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * @param \Illuminate\Http\Request $req
     * @return \Illuminate\Http\JsonResponse
     * desc This function get the application brief details 
     * request : saf_id (requirde)
     * ---------------Tables-----------------
     * active_saf_details            |
     * ward_mastrs                   | Saf details
     * property_type                 |
     * active_saf_owner_details      -> Saf Owner details
     * active_saf_floore_details     -> Saf Floore Details
     * workflow_tracks               |  
     * users                         | Comments and  date rolles
     * role_masters                  |
     * =======================================
     * helpers : Helpers/utility_helper.php   ->remove_null() -> for remove  null values
     */
    #Saf Details
    public function details(Request $req)
    {
        $req->validate([
            'id' => 'required|integer'
        ]);
        try {
            // Saf Details
            $data = [];
            $data = DB::table('active_safs')
                ->select('active_safs.*', 'w.ward_name as old_ward_no', 'o.ownership_type', 'p.property_type')
                ->join('ulb_ward_masters as w', 'w.id', '=', 'active_safs.ward_mstr_id')
                ->join('prop_m_ownership_types as o', 'o.id', '=', 'active_safs.ownership_type_mstr_id')
                ->leftJoin('prop_m_property_types as p', 'p.id', '=', 'active_safs.property_assessment_id')
                ->where('active_safs.id', $req->id)
                ->first();
            $data = json_decode(json_encode($data), true);
            $ownerDetails = ActiveSafsOwnerDtl::where('saf_id', $data['id'])->get();
            $data['owners'] = $ownerDetails;

            $floorDetails = ActiveSafsFloorDtls::where('saf_id', $data['id'])->get();
            $data['floors'] = $floorDetails;

            return responseMsg(true, 'Data Fetched', remove_null($data));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * @var userId Logged In User Id
     * desc This function set OR remove application on special category
     * request : escalateStatus (required, int type), safId(required)
     * -----------------Tables---------------------
     *  active_saf_details
     * ============================================
     * active_saf_details.is_escalate <- request->escalateStatus 
     * active_saf_details.escalate_by <- request->escalateStatus 
     * ============================================
     * #message -> return response 
     */
    #Add Inbox  special category
    public function postEscalate($request)
    {
        DB::beginTransaction();
        try {
            $userId = auth()->user()->id;
            // Validation Rule
            $rules = [
                "escalateStatus" => "required|int",
                "safId" => "required",
            ];
            // Validation Message
            $message = [
                "escalateStatus.required" => "Escalate Status Is Required",
                "safId.required" => "Saf Id Is Required",
            ];
            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }

            $saf_id = $request->safId;
            $data = ActiveSaf::find($saf_id);
            $data->is_escalate = $request->escalateStatus;
            $data->escalate_by = $userId;
            $data->save();
            DB::commit();
            return responseMsg(true, $request->escalateStatus == 1 ? 'Saf is Escalated' : "Saf is removed from Escalated", '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    /**
     * | @var ulbId authenticated user id
     * | @var ulbId authenticated ulb Id
     * | @var occupiedWard get ward by user id using trait
     * | @var wardId Filtered Ward ID from the collections
     * | @var safData SAF Data List
     * | @return
     * | @var \Illuminate\Support\Collection $safData
     */
    #Inbox  special category
    public function specialInbox()
    {
        try {
            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;
            $occupiedWard = $this->getWardByUserId($userId);                        // Get All Occupied Ward By user id using trait
            $wardId = $occupiedWard->map(function ($item, $key) {                   // Filter All ward_id in an array using laravel collections
                return $item->ward_id;
            });
            $safData = $this->getSaf()
                ->where('is_escalate', 1)
                ->where('active_safs.ulb_id', $ulbId)
                ->whereIn('ward_mstr_id', $wardId)
                ->groupBy('active_safs.id', 'active_safs.saf_no', 'ward.ward_name', 'p.property_type')
                ->get();
            return responseMsg(true, "Data Fetched", remove_null($safData));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Post Independent Comment
     * | @param mixed $request
     * | @var userId Logged In user Id
     * | @var levelPending The Level Pending Data of the Saf Id
     * | @return responseMsg
     */
    public function postIndependentComment($request)
    {
        try {
            DB::beginTransaction();
            $request->validate([
                'comment' => 'required',
                'safId' => 'required'
            ]);
            $userId = auth()->user()->id;
            $levelPending = PropLevelPending::where('saf_id', $request->safId)
                ->where('receiver_user_id', $userId)
                ->first();

            if (is_null($levelPending)) {
                $levelPending = PropLevelPending::where('saf_id', $request->safId)
                    ->orderByDesc('id')
                    ->limit(1)
                    ->first();
                if (is_null($levelPending)) {
                    return responseMsg(false, "SAF Not Found", "");
                }
            }
            $levelPending->remarks = $request->comment;
            $levelPending->receiver_user_id = $userId;
            $levelPending->save();

            // SAF Details
            $saf = ActiveSaf::find($request->safId);

            // Save On Workflow Track
            $workflowTrack = new WorkflowTrack();
            $workflowTrack->workflow_id = Config::get('workflow-constants.SAF_WORKFLOW_ID');
            $workflowTrack->citizen_id = $saf->user_id;
            $workflowTrack->module_id = Config::get('module-constants.PROPERTY_MODULE_ID');
            $workflowTrack->ref_table_dot_id = "active_safs.id";
            $workflowTrack->ref_table_id_value = $saf->id;
            $workflowTrack->message = $request->comment;
            $workflowTrack->commented_by = $userId;
            $workflowTrack->save();

            DB::commit();
            return responseMsg(true, "You Have Commented Successfully!!", ['Comment' => $request->comment]);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | @param mixed $request
     * | @var preLevelPending Get the Previous level pending data for the saf id
     * | @var levelPending new Level Pending to be add
     */
    # postNextLevel
    public function postNextLevel($request)
    {
        DB::beginTransaction();
        try {
            // previous level pending verification enabling
            $preLevelPending = PropLevelPending::where('saf_id', $request->safId)
                ->orderByDesc('id')
                ->limit(1)
                ->first();
            $preLevelPending->verification_status = '1';
            $preLevelPending->save();

            $levelPending = new PropLevelPending();
            $levelPending->saf_id = $request->safId;
            $levelPending->sender_role_id = $request->senderRoleId;
            $levelPending->receiver_role_id = $request->receiverRoleId;
            $levelPending->sender_user_id = auth()->user()->id;
            $levelPending->save();

            // SAF Application Update Current Role Updation
            $saf = ActiveSaf::find($request->safId);
            $saf->current_role = $request->receiverRoleId;
            $saf->save();

            DB::commit();
            return responseMsg(true, "Successfully Forwarded The Application!!", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    /**
     * | Approve or Reject The SAF Application
     * --------------------------------------------------
     * | ----------------- Initialization ---------------
     * | @param mixed $req
     * | @var activeSaf The Saf Record by Saf Id
     * | @var approvedSaf replication of the saf record to be approved
     * | @var rejectedSaf replication of the saf record to be rejected
     * ------------------- Alogrithm ---------------------
     * | $req->status (if 1 Application to be approved && if 0 application to be rejected)
     * ------------------- Dump --------------------------
     * | @return msg
     */
    public function safApprovalRejection($req)
    {
        $req->validate([
            'safId' => 'required|int',
            'status' => 'required|int'
        ]);
        try {
            DB::beginTransaction();
            // Approval
            if ($req->status == 1) {
                $safDetails = ActiveSaf::find($req->safId);
                $safDetails->holding_no = 'Hol/Ward/001';
                $safDetails->save();

                $activeSaf = ActiveSaf::query()
                    ->where('id', $req->safId)
                    ->first();
                $approvedSaf = $activeSaf->replicate();
                $approvedSaf->setTable('prop_approved_saf_details');
                $approvedSaf->id = $activeSaf->id;
                $approvedSaf->push();
                $activeSaf->delete();
                $msg = "Application Successfully Approved !! Holding No " . $safDetails->holding_no;
            }
            // Rejection
            if ($req->status == 0) {
                $activeSaf = ActiveSaf::query()
                    ->where('id', $req->safId)
                    ->first();
                $rejectedSaf = $activeSaf->replicate();
                $rejectedSaf->setTable('prop_rejected_saf_details');
                $rejectedSaf->id = $activeSaf->id;
                $rejectedSaf->push();
                $activeSaf->delete();
                $msg = "Application Rejected Successfully";
            }

            DB::commit();
            return responseMsg(true, $msg, "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Back to Citizen
     * | @param Request $req
     */
    public function backToCitizen($req)
    {
        try {
            $redis = Redis::connection();
            $backId = json_decode(Redis::get('workflow_roles'));
            if (!$backId) {
                $backId = WorkflowsWfRole::where('is_initiator', 1)->first();
                $redis->set('workflow_roles', json_encode($backId));
            }
            $saf = ActiveSaf::find($req->safId);
            $saf->current_role = $backId->id;
            $saf->save();
            return responseMsg(true, "Successfully Done", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
