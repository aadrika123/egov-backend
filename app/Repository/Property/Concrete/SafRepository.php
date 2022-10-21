<?php

namespace App\Repository\Property\Concrete;

use App\Repository\Property\Interfaces\iSafRepository;
use Illuminate\Http\Request;
use App\Models\Property\ActiveSafDetail;
use App\Models\ActiveSafFloorDetail;
use App\Models\ActiveSafOwnerDetail;
use App\Models\ActiveSafTaxe;
use App\Models\PropPropertie;
use App\Models\ObjectionTypeMstr;
use App\Models\PropertyObjection;
use App\Models\PropertyObjectionDetail;
use App\Models\PropFloorDetail;
use App\Models\PropOwner;
use App\Models\PropParamConstructionType;
use App\Models\PropParamFloorType;
use App\Models\PropParamOccupancyType;
use App\Models\PropParamOwnershipType;
use App\Models\PropParamPropertyType;
use App\Models\PropParamUsageType;
use App\Models\RoleMaster;
use App\Models\Saf;
use App\Models\UlbWardMaster;
use App\Models\UlbWorkflowMaster;
use App\Models\Ward\WardUser;
use App\Models\WardMstr;
use App\Models\Workflow;
use App\Models\WorkflowCandidate;
use App\Models\Workflows\UlbWorkflowRole;
use App\Models\WorkflowTrack;

use App\Traits\Auth;
use App\Traits\Property\WardPermission;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use App\EloquentClass\Property\dSafCalculation;
use App\EloquentClass\Property\dPropertyTax;
use App\Models\Property\PropApprovedSafDetail;
use App\Models\Property\PropLevelPending;
use App\Models\WfRoleusermap;
use App\Models\WfWardUser;
use App\Models\WfWorkflow;
use App\Traits\Workflow\Workflow as WorkflowTrait;
use App\Repository\Property\EloquentProperty;
use App\Traits\Property\SAF as GlobalSAF;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * | Created On-10-08-2022
 * | Created By-Anshu Kumar
 * -----------------------------------------------------------------------------------------
 * | SAF Module all operations 
 */
class SafRepository implements iSafRepository
{
    use Auth;               // Trait Used added by sandeep bara date 17-08-2022
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
                return responseMsg(false, "Invalid Assessment Type", $request->all());
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
            if ($request->getMethod() == "GET") {
                $data = [];
                $data['ward_master'] = $wardMaster;
                $ownershipTypes = PropParamOwnershipType::select('id', 'ownership_type')
                    ->where('status', 1)
                    ->get();
                $data['ownership_types'] = $ownershipTypes;
                $propertyType = PropParamPropertyType::select('id', 'property_type')
                    ->where('status', 1)
                    ->get();
                $data['property_type'] = $propertyType;
                $floorType = PropParamFloorType::select('id', 'floor_name')
                    ->where('status', 1)
                    ->get();
                $data['floor_type'] = $floorType;
                $usageType = PropParamUsageType::select('id', 'usage_type', 'usage_code')
                    ->where('status', 1)
                    ->get();
                $data['usage_type'] = $usageType;
                $occupancyType = PropParamOccupancyType::select('id', 'occupancy_type')
                    ->where('status', 1)
                    ->get();
                $data['occupancy_type'] = $occupancyType;
                $constructionType = PropParamConstructionType::select('id', "construction_type")
                    ->where('status', 1)
                    ->get();
                $data['construction_type'] = $constructionType;
                if (in_array($request->assessmentType, ["Reassessment", "Mutation"])) {
                    $propertyDtltl = $this->property->getPropertyById($request->previousHoldingId);
                    $data['property_dtl'] = remove_null($propertyDtltl);
                    $ownerDtl = $this->property->getOwnerDtlByPropId($request->previousHoldingId);
                    $data['owner_dtl'] = remove_null($ownerDtl);
                    $foolDtl = $this->property->getFloorDtlByPropId($request->previousHoldingId);
                    $data['fool_dtl'] = remove_null($foolDtl);
                }
                if (in_array($request->assessmentType, ["Mutation"])) {
                    $mutationMaster = $this->property->getAllTransferMode();
                    $data['mutation_master'] = remove_null($mutationMaster);
                }
                return  responseMsg(true, '', $data);
            }
            if ($request->getMethod() == "POST") {
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
                // return $this->saf->BuildingTax($inputs);
                // //$this->propertyTax->InsertTax(1,$this->saf->TotalTax);
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
                $saf = new ActiveSafDetail;
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
                // workflows
                $saf->citizen_id = $user_id;
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
                        $owner = new ActiveSafOwnerDetail;
                        $owner->saf_dtl_id = $saf->id;
                        $owner->owner_name = $owner_details['ownerName'] ?? null;
                        $owner->guardian_name = $owner_details['guardianName'] ?? null;
                        $owner->relation_type = $owner_details['relation'] ?? null;
                        $owner->mobile_no = $owner_details['mobileNo'] ?? null;
                        $owner->email = $owner_details['email'] ?? null;
                        $owner->pan_no = $owner_details['pan'] ?? null;
                        $owner->aadhar_no = $owner_details['aadhar'] ?? null;
                        $owner->emp_details_id =  $user_id ?? null;
                        $owner->rmc_saf_owner_dtl_id = $owner_details['rmcSafOwnerDtl'] ?? null;
                        $owner->rmc_saf_dtl_id = $owner_details['rmcSafDetail'] ?? null;
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
                        $floor = new ActiveSafFloorDetail();
                        $floor->saf_dtl_id = $saf->id;
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
            }
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
        $count = ActiveSafDetail::where('ward_mstr_id', $ward_id)
            ->where('ulb_id', $ulb_id)
            ->count() + 1;
        $ward_no = UlbWardMaster::select("ward_name")->where('id', $ward_id)->first()->ward_name;
        return $safNo = "SAF/" . str_pad($assessment_type, 2, '0', STR_PAD_LEFT) . "/" . str_pad($ward_no, 3, '0', STR_PAD_LEFT) . "/" . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    public function taxCalculater(Request $request)
    {
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

                $data = $this->getSaf()                                               // Global SAF 
                    ->where('active_saf_details.ulb_id', $ulbId)
                    ->where('active_saf_details.status', 1)
                    ->whereIn('current_role', $roleId)
                    ->orderByDesc('id')
                    ->groupBy('active_saf_details.id', 'p.property_type', 'ward.ward_name')
                    ->get();

                $occupiedWard = $this->getWardByUserId($userId);                        // Get All Occupied Ward By user id

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

            $safInbox = $this->getSaf()                                            // Global SAF 
                ->where('active_saf_details.ulb_id', $ulbId)
                ->where('current_role', null)
                ->where('active_saf_details.status', 1)
                ->whereIn('ward_mstr_id', $wardId)
                ->orderByDesc('id')
                ->groupBy('active_saf_details.id', 'p.property_type', 'ward.ward_name')
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
                ->groupBy('active_saf_details.id', 'p.property_type', 'ward.ward_name')
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
            $saf_id = $req->id;
            $user_id = auth()->user()->id;
            $role_id = ($this->getUserRoll($user_id)->role_id ?? -1);
            $ulb_id = auth()->user()->ulb_id;
            $saf_data = ActiveSafDetail::select(
                DB::raw("prop_param_property_types.property_type as property_type,
                                                       prop_param_ownership_types.ownership_type,
                                                       ulb_ward_masters.ward_name as ward_no
                                                      "),
                "active_saf_details.*"
            )
                ->join('ulb_ward_masters', function ($join) {
                    $join->on("ulb_ward_masters.id", "=", "active_saf_details.ward_mstr_id");
                })
                ->join('prop_param_property_types', function ($join) {
                    $join->on("prop_param_property_types.id", "=", "active_saf_details.prop_type_mstr_id")
                        ->where("prop_param_property_types.status", 1);
                })
                ->join('prop_param_ownership_types', function ($join) {
                    $join->on("prop_param_ownership_types.id", "=", "active_saf_details.ownership_type_mstr_id")
                        ->where("prop_param_ownership_types.status", 1);
                })
                ->where('active_saf_details.id', "=", $saf_id)
                ->first();
            $data = remove_null($saf_data);
            // if (!$saf_data->workflow_id || $role_id == -1) {
            //     throw new Exception("Workflow Not Found of This SAF !...");
            // }
            $owner_dtl = ActiveSafOwnerDetail::select('*')
                ->where('status', 1)
                ->where('saf_dtl_id', 1)
                ->get();
            $data['owner_dtl'] =  remove_null($owner_dtl);
            $floor = ActiveSafFloorDetail::select("*")
                ->where('status', 1)
                ->where('saf_dtl_id', $saf_id)
                ->get();
            $data['floor'] =  remove_null($floor);
            // $time_line =  DB::table('workflow_tracks')->select(
            //     "workflow_tracks.message",
            //     "role_masters.role_name",
            //     DB::raw("workflow_tracks.track_date::date as track_date")
            // )
            //     ->leftjoin('users', "users.id", "workflow_tracks.citizen_id")
            //     ->leftjoin('role_users', 'role_users.user_id', 'users.id')
            //     ->leftjoin('role_masters', 'role_masters.id', 'role_users.role_id')
            //     ->where('ref_table_dot_id', 'active_saf_details.id')
            //     ->where('ref_table_id_value', $saf_id)
            //     ->orderBy('track_date', 'desc')
            //     ->get();
            // $data['time_line'] =  remove_null($time_line);
            // $data['work_flow_candidate'] = [];
            // if ($saf_data->is_escalate) {
            //     $rol_type =  $this->getAllRoles($user_id, $ulb_id, $saf_data->workflow_id, $role_id);
            //     $data['work_flow_candidate'] =  remove_null(ConstToArray($rol_type));
            // }
            // $forward_backword =  $this->getForwordBackwordRoll($user_id, $ulb_id, $saf_data->workflow_id, $role_id);
            // $data['forward_backward'] =  remove_null($forward_backword);
            return responseMsg(true, 'Data Fetched', $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $saf_id);
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
            $data = ActiveSafDetail::find($saf_id);
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
                ->where('active_saf_details.ulb_id', $ulbId)
                ->whereIn('ward_mstr_id', $wardId)
                ->groupBy('active_saf_details.id', 'active_saf_details.saf_no', 'ward.ward_name', 'p.property_type')
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
            $saf = ActiveSafDetail::find($request->safId);
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
                $activeSaf = ActiveSafDetail::query()
                    ->where('id', $req->safId)
                    ->first();
                $approvedSaf = $activeSaf->replicate();
                $approvedSaf->setTable('prop_approved_saf_details');
                $approvedSaf->id = $activeSaf->id;
                $approvedSaf->push();
                $activeSaf->delete();
                $msg = "Application Successfully Approved";
            }
            // Rejection
            if ($req->status == 0) {
                $activeSaf = ActiveSafDetail::query()
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



    # add workflow_tracks
    public function workflowTracks(array $inputs)
    {
        try {
            $track = new WorkflowTrack();
            $track->workflow_candidate_id = $inputs['workflowCandidateID'] ?? null;
            $track->citizen_id = $inputs['citizenID'] ?? null;
            $track->module_id = $inputs['moduleID'] ?? null;
            $track->ref_table_dot_id = $inputs['refTableDotID'] ?? null;
            $track->ref_table_id_value = $inputs['refTableIDValue'] ?? null;
            $track->message = $inputs['message'] ?? null;
            $track->track_date = date('Y-m-d H:i:s');
            $track->forwarded_to = $inputs['forwardedTo'] ?? null;
            $track->save();
            $message = ["status" => true, "message" => "Successfully Saved The Remarks", "data" => ''];
            return $message;
        } catch (Exception $e) {
            return  ['status' => false, 'message' => $e->getMessage()];
        }
    }
    public function updateWorkflowTracks(array $where, array $values)
    {
        try {
            WorkflowTrack::where($where)->update($values);
            return true;
        } catch (Exception $e) {
            return  ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function setWorkFlowForwordBackword(Request $request)
    {
        try {
            $rules = [
                "ulbID" => "required",
                "workflowsID" => "required"
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), $request->all());
            }
            $ulbID = $request->ulbID;
            $workflowsID = $request->workflowsID;
            // if(!is_numeric($ulbID))
            // {
            //     $ulbID = Crypt::decrypt($ulbID);
            // }
            // if(!is_numeric($workflowsID))
            // {
            //     $workflowsID = Crypt::decrypt($workflowsID);
            // } dd("hhhh"); 
            $data = Workflow::select(DB::raw(" ulb_workflow_masters.ulb_id, 
                                            workflows.id as workflows_id,
                                            workflow_name,
                                            role_name,
                                            role_masters.id as rolle_id,
                                            case when show_full_list = true then  show_full_list 
                                                else false end as show_full_list,
                                            ulb_workflow_roles.id as ulb_workflow_roles_id
                                            "))
                ->join("ulb_workflow_masters", function ($join) {
                    $join->on("workflows.id", "=", "ulb_workflow_masters.workflow_id");
                })
                ->join("ulb_workflow_roles", function ($join) {
                    $join->on("ulb_workflow_roles.ulb_workflow_id", "=", "ulb_workflow_masters.id")
                        ->whereNull("ulb_workflow_roles.deleted_at");
                })
                ->join("role_masters", function ($join) {
                    $join->on("role_masters.id", "=", "ulb_workflow_roles.role_id");
                })
                ->where("ulb_workflow_masters.ulb_id", $ulbID)
                ->where("workflows.id", $workflowsID)
                ->orderBy("role_masters.id")
                ->get();
            $initiator = DB::select("select role_masters.id , role_name                             
                        from ulb_workflow_masters
                        inner join role_masters on role_masters.id = ulb_workflow_masters.initiator
                        where ulb_workflow_masters.ulb_id = $ulbID and ulb_workflow_masters.workflow_id = $workflowsID
                        and ulb_workflow_masters.deleted_at is null");

            $finisher = DB::select("select role_masters.id , role_name                             
            from ulb_workflow_masters
            inner join role_masters on role_masters.id = ulb_workflow_masters.finisher
            where ulb_workflow_masters.ulb_id = $ulbID and ulb_workflow_masters.workflow_id = $workflowsID
            and ulb_workflow_masters.deleted_at is null");
            if ($request->getMethod() == "GET") {
                $mapping =
                    $response["rolls"] = $data;
                $response["initiator"] = $initiator;
                $response["finisher"] = $finisher;
                return responseMsg(true, '', remove_null($response));
            } elseif ($request->getMethod() == "POST") {
                $rules = [
                    "ulbID"         => "required",
                    "workflowsID"   => "required",
                    "initiator"      => "required",
                    "finisher"      => "required",
                    "rolles"       => "required|array",
                    "rolles.*.ID"   => "required",
                    "rolles.*.forwodID" => "required",
                    "rolles.*.backwodID" => "required",
                    "rolles.*.showFullList" => "required|bool",
                ];
                $validator = Validator::make($request->all(), $rules);
                if ($validator->fails()) {
                    return responseMsg(false, $validator->errors(), $request->all());
                }
                $rolls = adjToArray($data);

                $data = array_map(function ($val) {
                    return $val['rolle_id'];
                }, adjToArray($data));
                $initiator = $request->initiator;
                $finisher = $request->finisher;
                $Rolles = $request->rolles;
                foreach ($Rolles as $key => $val) {
                    $id = $val['ID'];
                    $roll_name = array_filter($rolls, function ($val) use ($id) {
                        return $val['rolle_id'] == $id;
                    });
                    $roll_name = array_values($roll_name)[0] ?? [];
                    $Rolles[$key]["RollName"] = $roll_name["role_name"] ?? "Unknown User";
                    $Rolles[$key]["ulb_workflow_roles_id"] = $roll_name["ulb_workflow_roles_id"] ?? 0;
                }
                $message = array_map(function ($val) use ($initiator, $finisher, $data) {
                    if ($val['ID'] == $initiator && $val['backwodID'])
                        return "Initiator Has No Backword Id";
                    elseif ($val['ID'] == $finisher && $val['forwodID'])
                        return "Finisher Has No Forwrod Id";
                    elseif ((!$val['backwodID'] || !$val['forwodID']) && !in_array($val['ID'], [$initiator, $finisher]))
                        return $val['RollName'] . " Have Forword And Backword Id ";
                    elseif ($val['ID'] == $val['forwodID'] || $val['ID'] == $val['forwodID'])
                        return " Rolle " . $val['RollName'] . " Can't Forword And Backword Itself ";
                    elseif (!in_array($val['ID'], $data))
                        return " Undefind Role Id " . $val['ID'];
                }, $Rolles);

                $message["initiator"] = array_filter($Rolles, function ($val) use ($initiator) {
                    if ($val['ID'] == $initiator)
                        return true;
                });
                $message["finisher"] = array_filter($Rolles, function ($val) use ($finisher) {
                    if ($val['ID'] == $finisher)
                        return true;
                });
                if (sizeof($message["finisher"]) > 1)
                    $message["finisher"] = "Finisher Id Is Multyple Time";
                if (!$message["finisher"])
                    $message["finisher"] = "Finisher Is Not Inclueded";
                else
                    $message["finisher"] = [];
                if (sizeof($message["initiator"]) > 1)
                    $message["initiator"] = "Initiator Id Is Multyple Time";
                if (!$message["initiator"])
                    $message["initiator"] = "Initiator Is Not Inclueded";

                $mapping = array_map(function ($val) use ($Rolles) {
                    $ID = $val['ID'];
                    $FID = $val['forwodID'];
                    $BID = $val['backwodID'];
                    $RollName = $val['RollName'];
                    $m = array_map(function ($v) use ($ID, $FID, $BID, $RollName) {
                        if ($v['ID'] == $FID && $v['backwodID'] != $ID)
                            return "$RollName Has No Proper Forword Id";
                        elseif ($v['ID'] == $BID && $v['forwodID'] != $ID)
                            return "$RollName Has No Proper Backword Id";
                    }, $Rolles);
                    $m = array_filter($m, function ($val) {
                        return $val;
                    });
                    $m = array_values($m)[0] ?? [];
                    if (!empty($m))
                        return ($m);
                }, $Rolles);
                if (is_array($message["initiator"]))
                    $message["initiator"] = [];
                if (is_array($message["finisher"]))
                    $message["finisher"] = [];
                $message = array_filter($message, function ($val) {
                    return $val;
                });
                $message = array_values($message);
                $mapping = array_filter($mapping, function ($val) {
                    return $val;
                });
                $mapping = array_values($mapping);

                if ($mapping) {
                    foreach ($mapping as $key => $value) {
                        $message[] = $value;
                    }
                }
                if ($message) {
                    return responseMsg(false, $message, $request->all());
                }
                DB::beginTransaction();
                $UlbWorkflowMaster =  UlbWorkflowMaster::where("workflow_id", $workflowsID)
                    ->where("ulb_id", $ulbID)
                    ->update(["initiator" => $initiator, "finisher" => $finisher]);
                foreach ($Rolles as $val) {
                    $UlbWorkflowRoles = UlbWorkflowRole::find($val['ulb_workflow_roles_id']);
                    if (!$UlbWorkflowRoles) {
                        throw new Exception("Some Errors Please Contact To Admin");
                    }
                    $UlbWorkflowRoles->forward_id = $val['forwodID'] ? $val['forwodID'] : null;
                    $UlbWorkflowRoles->backward_id = $val['backwodID'] ? $val['backwodID'] : null;
                    $UlbWorkflowRoles->show_full_list = $val['showFullList'] ? $val['showFullList'] : false;
                    $UlbWorkflowRoles->save();
                }
                DB::commit();
                return responseMsg(true, "Workflow Memeber Add Succsessfully", "");
            }
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
}
