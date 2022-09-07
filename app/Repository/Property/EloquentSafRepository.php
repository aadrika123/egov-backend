<?php

namespace App\Repository\Property;

use App\Repository\Property\SafRepository;
use Illuminate\Http\Request;
use App\Models\ActiveSafDetail;
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
use App\Traits\Property\PropertyCal;
use App\Traits\Property\WardPermission;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

/**
 * | Created On-10-08-2022
 * | Created By-Anshu Kumar
 * -----------------------------------------------------------------------------------------
 * | SAF Module all operations 
 */
class EloquentSafRepository implements SafRepository
{
    use Auth;               // Trait Used added by sandeep bara date 17-08-2022
    use WardPermission;
    use PropertyCal;

    /**
     * | Citizens Applying For SAF
     * | Proper Validation will be applied after 
     * | @param Illuminate\Http\Request
     * | @param Request $request
     * | @param response
     */
    protected $property;
    public function __construct()
    { 
        $this->property = new EloquentProperty;
    }
    public function applySaf(Request $request)
    { 
        $message=["status"=>false,"data"=>$request->all(),"message"=>""];
        $user_id = auth()->user()->id; 
        $isCitizen = auth()->user()->user_type=="Citizen"?true:false;
        // return $this->buildingRulSet1(auth()->user()->ulb_id,500,1,1,1,2,true,'1540-04-01');
        // return $this->buildingRulSet2(auth()->user()->ulb_id,500,12,2,40,1,'2020-04-01');
        return $this->buildingRulSet3(auth()->user()->ulb_id,500,12,2,19.9919,1,true,'2020-04-01');

        try {
            
            // Determining the initiator and finisher id
            $workflow_id = Config::get('workflow-constants.SAF_WORKFLOW_ID');
            $ulb_id = auth()->user()->ulb_id;
            $workflows = UlbWorkflowMaster::select('initiator', 'finisher')
                ->where('ulb_id', $ulb_id)
                ->where('workflow_id', $workflow_id)
                ->first();
            if(!$workflows)
            {
                return responseMsg(false,"Workflow Not Available",$request->all());  
            }
            if(!in_array($request->assessmentType,["NewAssessment","Reassessment","Mutation"]))
            {
                return responseMsg(false,"Invalid Assessment Type",$request->all());  
            }
            $rules=[];
            if(in_array($request->assessmentType,["Reassessment","Mutation"]))
            {
                $rules["previousHoldingId"]="required";
                $message["previousHoldingId.required"]="Old Property Id Requird";   
                $rules["holdingNo"] ="required";   
                $message["holdingNo.required"]="holding No. Is Requird";         
            }        
            $validator = Validator::make($request->all(),$rules,$message);  
            if($validator->fails())
            {  
                return responseMsg(false,$validator->errors(),$request->all());
            }
            if($request->getMethod()=="GET")
            {
                $data=[];
                $wardMaster = UlbWardMaster::select('id','ward_name')
                                ->where('ulb_id',$ulb_id)
                                ->get();
                $data['ward_master']=$wardMaster;
                $ownershipTypes = PropParamOwnershipType::select('id','ownership_type')
                                    ->where('status',1)
                                    ->get();
                $data['ownership_types']=$ownershipTypes;
                $propertyType = PropParamPropertyType::select('id','property_type')
                                    ->where('status',1)
                                    ->get();
                $data['property_type']=$propertyType;
                $floorType = PropParamFloorType::select('id','floor_name')
                                ->where('status',1)
                                ->get();
                $data['floor_type']=$floorType;
                $usageType = PropParamUsageType::select('id','usage_type','usage_code')
                                            ->where('status',1)
                                            ->get();
                $data['usage_type']=$usageType;
                $occupancyType = PropParamOccupancyType::select('id','occupancy_type')
                                 ->where('status',1)
                                 ->get();
                $data['occupancy_type']=$occupancyType;
                $constructionType = PropParamConstructionType::select('id',"construction_type")
                                     ->where('status',1)
                                     ->get();
                $data['construction_type']=$constructionType;
                if(in_array($request->assessmentType,["Reassessment","Mutation"]))
                {
                    $propertyDtltl = $this->property->getPropertyById($request->previousHoldingId);
                    $data['property_dtl']= remove_null($propertyDtltl);
                    $ownerDtl = $this->property->getOwnerDtlByPropId($request->previousHoldingId);
                    $data['owner_dtl']= remove_null($ownerDtl);
                    $foolDtl = $this->property->getFloorDtlByPropId($request->previousHoldingId);
                    $data['fool_dtl']= remove_null($foolDtl);
                }
                if(in_array($request->assessmentType,["Mutation"]))
                {
                    $mutationMaster = $this->property->getAllTransferMode();
                    $data['mutation_master']= remove_null($mutationMaster);
                }
                return  responseMsg(true,'',$data);
            }
            elseif($request->getMethod()=="POST")
            {
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

                $validator = Validator::make($request->all(),$rules,$message);  
                if($validator->fails())
                {  
                    return responseMsg(false,$request->all(),$validator->errors());
                }

                $request->assessmentType = $request->assessmentType=="NewAssessment"?"New Assessment":$request->assessmentType;
                if($request->roadType<=0)
                    $request->roadType=4;
                elseif($request->roadType>0 && $request->roadType<20)
                    $request->roadType=3;
                elseif($request->roadType>=20 && $request->roadType<=39)
                    $request->roadType=2;
                elseif($request->roadType>40)
                    $request->roadType=1;
                DB::beginTransaction();
                $assessmentTypeId=Config::get("PropertyConstaint.ASSESSMENT-TYPE.".$request->assessmentType);
                // dd($request->ward);
                $safNo = $this->safNo($request->ward,$assessmentTypeId,$ulb_id);
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
                $saf->current_user = $workflows->initiator;
                $saf->initiator_id = $workflows->initiator;
                $saf->finisher_id = $workflows->finisher;
                $saf->workflow_id = $workflow_id;
                $saf->ulb_id = $ulb_id;
                
                $saf->save();
                
                // SAF Owner Details
                if ($request['owner']) {
                    $owner_detail = $request['owner'];
                    foreach ($owner_detail as $owner_details) {
                        $owner = new ActiveSafOwnerDetail;
                        $owner->saf_dtl_id = $saf->id;
                        $owner->owner_name = $owner_details['ownerName']??null;
                        $owner->guardian_name = $owner_details['guardianName']??null;
                        $owner->relation_type = $owner_details['relation']??null;
                        $owner->mobile_no = $owner_details['mobileNo']??null;
                        $owner->email = $owner_details['email']??null;
                        $owner->pan_no = $owner_details['pan']??null;
                        $owner->aadhar_no = $owner_details['aadhar']??null;
                        $owner->emp_details_id =  $user_id ??null;
                        $owner->rmc_saf_owner_dtl_id = $owner_details['rmcSafOwnerDtl']??null;
                        $owner->rmc_saf_dtl_id = $owner_details['rmcSafDetail']??null;
                        $owner->gender = $owner_details['gender']??null;
                        $owner->dob = $owner_details['dob']??null;
                        $owner->is_armed_force = $owner_details['isArmedForce']??null;
                        $owner->is_specially_abled = $owner_details['isSpeciallyAbled']??null;
                        $owner->save();
                    }
                }
    
                // Floor Details
                if ($request['floor']) {
                    $floor_detail = $request['floor'];
                    foreach ($floor_detail as $floor_details) {
                        $floor = new ActiveSafFloorDetail();
                        $floor->saf_dtl_id = $saf->id;
                        $floor->floor_mstr_id = $floor_details['floorNo']??null;
                        $floor->usage_type_mstr_id = $floor_details['useType']??null;
                        $floor->const_type_mstr_id = $floor_details['constructionType']??null;
                        $floor->occupancy_type_mstr_id = $floor_details['occupancyType']??null;
                        $floor->builtup_area = $floor_details['buildupArea']??null;
                        $floor->date_from = $floor_details['dateFrom']??null;
                        $floor->date_upto = $floor_details['dateUpto']??null;
                        $floor->prop_floor_details_id = $floor_details['propFloorDetail']??null;
                        $floor->save();
                    }
                }
    
                DB::commit();
                return responseMsg(true,"Successfully Submitted Your Application Your SAF No. $safNo",["safNo"=>$safNo]);
            }
        } 
        catch (Exception $e) 
        {
            DB::rollBack();
            return $e;
        }
    }
    public function getPropIdByWardNoHodingNo(Request $request)
    {
        try{
            $rules=[
                "wardId"=>"required",
                "holdingNo"=>"required",
            ];
            $message = [
                "wardId.required"=>"Ward id required",
                "holdingNo.required"=>"Holding No required",
            ];         
            $validator = Validator::make($request->all(),$rules,$message);  
            if($validator->fails())
            {  
                return responseMsg(false,$validator->errors(),$request->all());
            }
            else
            {
                $inputs['ward_mstr_id'] = $request->ward_id;
                $inputs['holding_no'] = $request->holding_no;
                $data = $this->property->getPropIdByWardNoHodingNo($inputs);
                return responseMsg(true,'',$data,);

            }
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
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
    public function safNo($ward_id,$assessment_type,$ulb_id)
    { 
        $count = ActiveSafDetail::where('ward_mstr_id',$ward_id)
                                ->where('ulb_id',$ulb_id)
                                ->count()+1;
        $ward_no = UlbWardMaster::select("ward_name")->where('id',$ward_id)->first()->ward_name;    
        return $safNo = "SAF/".str_pad($assessment_type,2,'0',STR_PAD_LEFT)."/".str_pad($ward_no,3,'0',STR_PAD_LEFT)."/".str_pad($count,5,'0',STR_PAD_LEFT);
    }

    public function taxCalculater(Request $request)
    {

    }

    /**
         * desc This function list the application according to permmited ward_no for the user_roll
         * request : key (optional) for seraching
         * #---------------Tables------------------
         * activ_saf_details                |
         * active_saf_owner_details         |  for listing data
         * workflow_candidates              |  
         * ulb_workflow_masters             |  for check loging user is authorized or Not for WorkFlow
         * users                           ->  for get ulb_id
         * ===================================================
         * 
    */
   #Inbox
   public function inbox($key)
   {  
        try{
            $user_id = auth()->user()->id;
            $redis=Redis::connection();  // Redis Connection
            $redis_data = json_decode(Redis::get('user:' . $user_id),true);
            $ulb_id = $redis_data['ulb_id']??auth()->user()->ulb_id;
            $roll_id = 1;// $redis_data['role_id']??auth()->user()->roll_id;
            $workflow_id = Config::get('workflow-constants.SAF_WORKFLOW_ID');        
            $work_flow_candidate = $this->work_flow_candidate($user_id,$ulb_id);
            if(!$work_flow_candidate)
            {
                throw new Exception("Your Are Not Authoried");
            }        
            $work_flow_candidate = collect($work_flow_candidate);         
            $ward_permission = $this->WardPermission($user_id);
            $ward_ids = array_map(function($val)
                        {
                            return $val['ulb_ward_id'];
                        },$ward_permission); 
            $data = ActiveSafDetail::select(DB::raw("owner_name,
                                                    guardian_name ,
                                                    mobile_no,
                                                   'SAF' as assessment_type,
                                                    'VacentLand' as property_type,
                                                    ulb_ward_masters.ward_name as ward_no,
                                                    active_saf_details.created_at::date as apply_date") ,
                                           "active_saf_details.id",
                                           "active_saf_details.saf_no",
                                           "active_saf_details.id") 
                                           ->join('ulb_ward_masters', function($join){
                                                $join->on("ulb_ward_masters.id","=","active_saf_details.ward_mstr_id");
                                            })
                                           ->leftJoin(
                                               DB::raw("(SELECT active_saf_owner_details.saf_dtl_id,
                                                               string_agg(active_saf_owner_details.owner_name,', ') as owner_name,
                                                               string_agg(active_saf_owner_details.guardian_name,', ') as guardian_name,
                                                               string_agg(active_saf_owner_details.mobile_no::text,', ') as mobile_no
                                                          FROM active_saf_owner_details 
                                                          WHERE active_saf_owner_details.status = 1
                                                          GROUP BY active_saf_owner_details.saf_dtl_id
                                                          )active_saf_owner_details
                                                           "),
                                               function($join){
                                                   $join->on("active_saf_owner_details.saf_dtl_id","=","active_saf_details.id")
                                                   ;
                                               }
                                           )
                                           ->where("active_saf_details.current_user",$roll_id)
                                           ->where("active_saf_details.status",1) 
                                           ->where("active_saf_details.ulb_id",$ulb_id)  
                                           ->whereIn('active_saf_details.ward_mstr_id',$ward_ids) ;    
            if($key)
            {
                $data= $data->where(function($query) use($key)
                                {
                                    $query->orwhere('active_saf_details.holding_no', 'ILIKE', '%'.$key.'%')
                                    ->orwhere('active_saf_details.saf_no', 'ILIKE', '%'.$key.'%')
                                    ->orwhere('active_saf_owner_details.owner_name', 'ILIKE', '%'.$key.'%')
                                    ->orwhere('active_saf_owner_details.guardian_name', 'ILIKE', '%'.$key.'%')
                                    ->orwhere('active_saf_owner_details.mobile_no', 'ILIKE', '%'.$key.'%');
                                });
            }        
            $saf=$data->get() ->map(function($data) {
                    if ( ! $data->owner_name) {
                        $data->owner_name = '';
                    }
                    if ( ! $data->guardian_name) {
                        $data->guardian_name = '';
                    } 
                    if ( ! $data->mobile_no) {
                        $data->mobile_no = '';
                    }
                    if ( ! $data->assessment_type) {
                        $data->assessment_type = '';
                    }
                    if ( ! $data->ward_no) {
                        $data->ward_no = '';
                    }
                    if ( ! $data->property_type) {
                        $data->property_type = '';
                    }
                    if ( ! $data->id) {
                        $data->id = '';
                    } 
                    if ( ! $data->saf_no) {
                        $data->saf_no = '';
                    }                                                
                    return $data;
            });
            $data=remove_null(['ulb_id'=>$ulb_id,
                'user_id'=>$user_id,
                'roll_id'=>$roll_id,
                'workflow_id'=>$workflow_id,
                'work_flow_candidate_id'=>$work_flow_candidate['id'],
                'module_id'=>$work_flow_candidate['module_id'],
                "data_list"=>adjToArray($saf),
                ],true,['ulb_id','user_id','roll_id','workflow_id','module_id','id']);
            return responseMsg(true,'',$data);
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$key);
        }
   }

   /**
         * desc This function list the application according to permmited ward_no for the user_roll
         * request : key (optional) for seraching
         * #---------------Tables------------------
         * activ_saf_details                |
         * active_saf_owner_details         |  for listing data
         * workflow_candidates              |  
         * ulb_workflow_masters             |  for check loging user is authorized or Not for WorkFlow
         * users                           ->  for get ulb_id
         * ===================================================
         * 
   */    
   #OutBox
   public function outbox($key)
   {    
        try{

            $user_id = auth()->user()->id; 
            $redis=Redis::connection();  // Redis Connection
            $redis_data = json_decode(Redis::get('user:' . $user_id),true);
            $ulb_id = $redis_data['ulb_id']??auth()->user()->ulb_id;;
            $roll_id =  $redis_data['role_id']??auth()->user()->roll_id;; 
            $workflow_id = Config::get('workflow-constants.SAF_WORKFLOW_ID');
            $work_flow_candidate = $this->work_flow_candidate($user_id,$ulb_id);
            if(!$work_flow_candidate)
            {
                throw new Exception("Your Are Not Authoried");
            }
            $work_flow_candidate = collect($work_flow_candidate);
            $ward_permission = $this->WardPermission($user_id);
            $ward_ids = array_map(function($val)
                        {
                            return $val['ulb_ward_id'];
                        },$ward_permission);        
            $data = ActiveSafDetail::select(
                               DB::raw("owner_name,
                                   guardian_name ,
                                   mobile_no,
                                   assessment_type as assessment_type,
                                   property_type as property_type,
                                    ulb_ward_masters.ward_name as ward_no,
                                    active_saf_details.created_at::date as apply_date,
                                    active_saf_details.id") ,
                               
                               "active_saf_details.saf_no") 
                               ->join('ulb_ward_masters', function($join){
                                    $join->on("ulb_ward_masters.id","=","active_saf_details.ward_mstr_id");
                                }) 
                                ->join('prop_param_property_types', function($join){
                                    $join->on("prop_param_property_types.id","=","active_saf_details.prop_type_mstr_id")
                                    ->where("prop_param_property_types.status",1);
                                })
                                ->join('prop_param_ownership_types', function($join){
                                    $join->on("prop_param_ownership_types.id","=","active_saf_details.ownership_type_mstr_id")
                                    ->where("prop_param_ownership_types.status",1);
                                })
                               ->leftJoin(
                                   DB::raw("(SELECT active_saf_owner_details.saf_dtl_id,
                                                   string_agg(active_saf_owner_details.owner_name,', ') as owner_name,
                                                   string_agg(active_saf_owner_details.guardian_name,', ') as guardian_name,
                                                   string_agg(active_saf_owner_details.mobile_no::text,', ') as mobile_no
                                              FROM active_saf_owner_details 
                                              WHERE active_saf_owner_details.status = 1
                                              GROUP BY active_saf_owner_details.saf_dtl_id
                                              )active_saf_owner_details
                                               "),
                                   function($join){
                                       $join->on("active_saf_owner_details.saf_dtl_id","=","active_saf_details.id");
                                   }
                               )
                               ->where(
                                   function($query) use($roll_id){
                                       return $query
                                       ->where('active_saf_details.current_user', '<>', $roll_id)
                                       ->orwhereNull('active_saf_details.current_user');
                               })
                               ->where("active_saf_details.status",1)
                               ->where("active_saf_details.ulb_id",$ulb_id)
                               ->whereIn('active_saf_details.ward_mstr_id',$ward_ids) ;
                               if($key)
                               {
                                   $data= $data->where(function($query) use($key)
                                                   {
                                                       $query->orwhere('active_saf_details.holding_no', 'ILIKE', '%'.$key.'%')
                                                       ->orwhere('active_saf_details.saf_no', 'ILIKE', '%'.$key.'%')
                                                       ->orwhere('active_saf_owner_details.owner_name', 'ILIKE', '%'.$key.'%')
                                                       ->orwhere('active_saf_owner_details.guardian_name', 'ILIKE', '%'.$key.'%')
                                                       ->orwhere('active_saf_owner_details.mobile_no', 'ILIKE', '%'.$key.'%');
                                                   });
                               }
            $saf=$data->get(); 
            $data=remove_null(['ulb_id'=>$ulb_id,
                'user_id'=>$user_id,
                'roll_id'=>$roll_id,
                'workflow_id'=>$workflow_id,
                'work_flow_candidate_id'=>$work_flow_candidate['id'],
                'module_id'=>$work_flow_candidate['module_id'],
                "data_list"=>$saf,
                ],true,['ulb_id','user_id','roll_id','workflow_id','module_id','id']);
           
            return responseMsg(true,'',$data);
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$key);
        }
   }

   /**
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
   public function details($saf_id)
   { 
      try{
          if(!is_numeric($saf_id))
          {
            $saf_id = Crypt::decrypt($saf_id);
          }
          $user_id = auth()->user()->id;
          $role_id = auth()->user()->roll_id;
          $ulb_id = auth()->user()->ulb_id;
          $saf_data = ActiveSafDetail::select(DB::raw("prop_param_property_types.property_type as property_type,
                                                       prop_param_ownership_types.ownership_type,
                                                       ulb_ward_masters.ward_name as ward_no
                                                      "),
                                              "active_saf_details.*"
                                              )
                                              ->join('ulb_ward_masters', function($join){
                                                   $join->on("ulb_ward_masters.id","=","active_saf_details.ward_mstr_id");
                                               })
                                               ->join('prop_param_property_types', function($join){
                                                   $join->on("prop_param_property_types.id","=","active_saf_details.prop_type_mstr_id")
                                                   ->where("prop_param_property_types.status",1);
                                               })
                                               ->join('prop_param_ownership_types', function($join){
                                                   $join->on("prop_param_ownership_types.id","=","active_saf_details.ownership_type_mstr_id")
                                                   ->where("prop_param_ownership_types.status",1);
                                               })
                                              ->where('active_saf_details.id',"=",$saf_id)             
                                              ->first();
          $data = remove_null($saf_data,true); 
          if(!$saf_data->workflow_id )
          {
              throw new Exception("Workflow Not Found of This SAF !...");
          }
          $owner_dtl = ActiveSafOwnerDetail::select('*')
                                                  ->where('status',1)
                                                  ->where('saf_dtl_id',$saf_id)
                                                  ->get();
          $data['owner_dtl'] =  remove_null($owner_dtl); 
          $floor = ActiveSafFloorDetail::select("*")
                                      ->where('status',1)
                                      ->where('saf_dtl_id',$saf_id)   
                                      ->get(); 
          $data['floor'] =  remove_null($floor);
          $time_line =  DB::table('workflow_tracks')->select("workflow_tracks.message","role_masters.role_name",
                                                               DB::raw("workflow_tracks.track_date::date as track_date")
                                                               )                            
                                   ->leftjoin('users',"users.id","workflow_tracks.citizen_id")
                                   ->leftjoin('role_masters','role_masters.id','users.roll_id')
                                   ->where('ref_table_dot_id','active_saf_details.id')
                                   ->where('ref_table_id_value',$saf_id)
                                   ->orderBy('track_date','desc')
                                   ->get();
           $data['time_line'] =  remove_null($time_line);
           $data['work_flow_candidate']=[];
           if($saf_data->is_escalate)
           {
               $rol_type =  $this->getAllRoles($user_id,$ulb_id,$saf_data->workflow_id,$role_id); 
               $data['work_flow_candidate'] =  remove_null(ConstToArray($rol_type));         
           }
           $forward_backword =  $this->getForwordBackwordRoll($user_id,$ulb_id,$saf_data->workflow_id,$role_id);        
           $data['forward_backward'] =  remove_null($forward_backword);        
           return responseMsg(true,'',$data);
      }
      catch(Exception $e)
      {
        return responseMsg(false,$e->getMessage(),$saf_id);
      }
   }   

   /**
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
   public function special(Request $request)
   {    
        DB::beginTransaction();
        try{

            $rules=[
                "escalateStatus"=>"required|int",
                "safId"=>"required", 
            ];
            $message = [
                "escalateStatus.required"=>"Escalate Status Is Required",
                "safId.required"=>"Saf Id Is Required",
            ];
            $validator = Validator::make($request->all(),$rules,$message);  
            if($validator->fails())
            {  
                return responseMsg( false, $validator->errors(),$request->all());
            }
    
            $user_id = auth()->user()->id;
            $saf = new ActiveSafDetail;
            
            $saf_id = $request->id??$request->safId;
            if(!is_numeric($saf_id))
            {
                $saf_id = Crypt::decrypt($saf_id);
            }
            $data = $saf->where('current_user',$user_id)->find($saf_id);
            if(!$data)
            { 
                throw new Exception("Saf Not Found");
            }
            $data->is_escalate=$request->escalateStatus;  
            $data->escalate_by=$user_id;        
            $data->save();
            DB::commit();
            return responseMsg( true, $request->escalateStatus==1?'Saf is Escalated':"Saf is removed from Escalated",'');
        }
        catch(Exception $e)
        {   DB::rollBack();
            return responseMsg( false, $e->getMessage(),$request->all());
        }
   }

   /**
    * desc This function get the Special Category Application
    * request : key (optional) -> for searching
    * #---------------Tables------------------
    * activ_saf_details                |
    * active_saf_owner_details         |  for listing data
    * workflow_candidates              |  
    * ulb_workflow_masters             |  for check loging user is authorized or Not for WorkFlow
    * users                           ->  for get ulb_id
    * ===================================================
   */
   #Inbox  special category
   public function specialInbox($key)
   {
        try{
            
            $user_id = auth()->user()->id;
            $redis=Redis::connection();  // Redis Connection
            $redis_data = json_decode(Redis::get('user:' . $user_id),true);
            $ulb_id = $redis_data['ulb_id']??auth()->user()->ulb_id;;
            $roll_id =  $redis_data['role_id']??auth()->user()->roll_id;; 
            $workflow_id = Config::get('workflow-constants.SAF_WORKFLOW_ID');
            $work_flow_candidate = $this->work_flow_candidate($user_id,$ulb_id);
            if(!$work_flow_candidate)
            {
                throw new Exception("Your Are Not Authoried");
               
            }        
            $work_flow_candidate = collect($work_flow_candidate); 
            $ward_permission = $this->WardPermission($user_id);
            $ward_ids = array_map(function($val)
                        {
                            return $val['ulb_ward_id'];
                        },$ward_permission);             
            $data = ActiveSafDetail::select(DB::raw("owner_name,
                                                    guardian_name ,
                                                    mobile_no,
                                                    'SAF' as assessment_type,
                                                    'VacentLand' as property_type,
                                                    ulb_ward_masters.ward_name as ward_no,
                                                    active_saf_details.created_at::date as apply_date") ,
                                            "active_saf_details.id",
                                            "active_saf_details.saf_no",
                                            "active_saf_details.id") 
                                            ->join('ulb_ward_masters', function($join){
                                                $join->on("ulb_ward_masters.id","=","active_saf_details.ward_mstr_id");
                                                
                                            })
                                            ->leftJoin(
                                                DB::raw("(SELECT active_saf_owner_details.saf_dtl_id,
                                                                string_agg(active_saf_owner_details.owner_name,', ') as owner_name,
                                                                string_agg(active_saf_owner_details.guardian_name,', ') as guardian_name,
                                                                string_agg(active_saf_owner_details.mobile_no::text,', ') as mobile_no
                                                        FROM active_saf_owner_details 
                                                        WHERE active_saf_owner_details.status = 1
                                                        GROUP BY active_saf_owner_details.saf_dtl_id
                                                        )active_saf_owner_details
                                                            "),
                                                function($join){
                                                    $join->on("active_saf_owner_details.saf_dtl_id","=","active_saf_details.id")
                                                    ;
                                                }
                                            )
                                            ->where("active_saf_details.current_user",$roll_id)
                                            ->where("active_saf_details.status",1)   
                                            ->where("active_saf_details.ulb_id",$ulb_id)          
                                            ->where('is_escalate',1)
                                            ->whereIn('active_saf_details.ward_mstr_id',$ward_ids) ;
           
            if($key)
            {
                $data= $data->where(function($query) use($key)
                                {
                                    $query->orwhere('active_saf_details.holding_no', 'ILIKE', '%'.$key.'%')
                                    ->orwhere('active_saf_details.saf_no', 'ILIKE', '%'.$key.'%')
                                    ->orwhere('active_saf_owner_details.owner_name', 'ILIKE', '%'.$key.'%')
                                    ->orwhere('active_saf_owner_details.guardian_name', 'ILIKE', '%'.$key.'%')
                                    ->orwhere('active_saf_owner_details.mobile_no', 'ILIKE', '%'.$key.'%');
                                });
            }
            $saf=$data->get() ->map(function($data) {
                    if ( ! $data->owner_name) {
                        $data->owner_name = '';
                    }
                    if ( ! $data->guardian_name) {
                        $data->guardian_name = '';
                    } 
                    if ( ! $data->mobile_no) {
                        $data->mobile_no = '';
                    }
                    if ( ! $data->assessment_type) {
                        $data->assessment_type = '';
                    }
                    if ( ! $data->ward_no) {
                    $data->ward_no = '';
                    }
                    if ( ! $data->property_type) {
                    $data->property_type = '';
                    }
                    if ( ! $data->id) {
                        $data->id = '';
                    } 
                    if ( ! $data->saf_no) {
                        $data->saf_no = '';
                    }                                                
                    return $data;
                });         
            $data=remove_null(['ulb_id'=>$ulb_id,
                'user_id'=>$user_id,
                'roll_id'=>$roll_id,
                'workflow_id'=>$workflow_id,
                'work_flow_candidate_id'=>$work_flow_candidate['id'],
                'module_id'=>$work_flow_candidate['module_id'],
                "data_list"=>$saf,
                ],true,['ulb_id','user_id','roll_id','workflow_id','module_id','id']);
            return responseMsg(true,'',$data);
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$key);
        }

   }

   /**
      * CURRENT USER SENDS APPLICATION TO NEXT LEVEL
      * # HANDLE TO CASES- 1. SEND TO ANY MEMBER, 2. SEND NEXT LEVEL UP/DOWN
      * # Update current_user of table active_saf_details for next user
      * # Save comments to workflowtracks table using function workflowTracks()
      *----------------Tables--------------------
      * active_saf_details
      * workflow_candidates
      * ulb_workflow_masters
      * users
      * #====================================
      * varialbles
      * $messages -> for reapons 1. status 2. data 3. Message
      * $user_id <- users.id
      * $saf_id <- request->id??request->safId;
      * $saf <- ActiveSafDetail ->obj (instanse create)
      * $data <- ActiveSafDetail -> find and store active_saf_details data ** [if data is not found return error Message] **
      * $work_flow_candidate  <- 'workflow_candidates.id',"ulb_workflow_masters.module_id" -> check loging user is authorize for this workFlow or not
      * active_saf_details.current_user <- request->receiverId
      * coll workflowTracks() for comment store
      * 
   */
   # postNextLevel
   public function postNextLevel(Request $request)
   {    
        DB::beginTransaction();
        try{
            $user_id = auth()->user()->id;
            $roll_type_id = auth()->user()->roll_id;
            $saf = new ActiveSafDetail;
            $saf_id = $request->safId;
            if(!is_numeric($saf_id))
            {
                $saf_id = Crypt::decrypt($saf_id);
            }
            $data = $saf->where('current_user',$roll_type_id)->find($saf_id);
            if(!$data)
            {
                $message=["status"=>false,"data"=>$request->all(),"message"=>"Saf Not Found"];
                return response()->json($message,200);
            }
            $regex = '/^[a-zA-Z1-9][a-zA-Z1-9\\s]+$/';
            $rules=[
                "ulbId"=>"required",
                "receiverId"=>"required|int",
                "btn" =>"required|in:btc,forward,backword",
                "comment"=>"required|min:10|regex:$regex",
            ];
            $message = [
                "ulbId.required"=>"Ulb Id Is Required",
                "receiverDesignationId.required"=>"Receiver User Id Is Required",
                "receiverId.int"=>"Receiver User Id Must Be Integer",
                "comment.required"=>"Comment Is Required",
                "comment.min"=>"Comment Length At Least 10 Charecters",
            ];
            $validator = Validator::make($request->all(),$rules,$message);  
            if($validator->fails())
            {  
                return responseMsg(false,$validator->errors(),$request->all());
            }            
            $work_flow_candidate = WorkflowCandidate::select('workflow_candidates.id',"ulb_workflow_masters.module_id")
                                    ->join('ulb_workflow_masters','ulb_workflow_masters.id','workflow_candidates.ulb_workflow_id')
                                    ->where('workflow_candidates.user_id',$user_id)
                                    ->where('ulb_workflow_masters.ulb_id',$request->ulbId)
                                    ->first();
            if(!$work_flow_candidate)
            {
                throw new Exception("work_flow_candidate not found");
            }
            $dd=[];
            if($data->finisher_id = $roll_type_id && strtoupper($request->btn)=="FORWARD")
            {
                $dd = $this->property->transFerSafToProperty($data,$user_id);
                if(!$dd)
                {
                    throw new Exception("Some Error Occurse Pleste Contacte To Admin123");
                }

            }
            else
            {
                $data->current_user=$request->receiverId;
                $data->save();
            }
            $inputs=['workflowCandidateID'=>$work_flow_candidate->id,
                    "citizenID"=>$user_id,
                    "moduleID"=>$work_flow_candidate->module_id,
                    "refTableDotID"=>'active_saf_details.id',
                    "refTableIDValue"=>$saf_id,
                    "message"=>$request->comment,
                    "forwardedTo"=>$request->receiverId
            ];
            $workfloes = $this->workflowTracks($inputs);
            if($workfloes['status']==false)
            {  
                throw new Exception($workfloes['message']);
            }
            if($dd)
            {
                $where=["ref_table_dot_id"=>"active_saf_details.id","ref_table_id_value"=>$saf_id];
                $values=["ref_table_dot_id"=>"safs.id","ref_table_id_value"=>$dd["saf_id"]];
                if(!$this->updateWorkflowTracks($where,$values))
                {
                    throw new Exception("Some Error Occurse Pleste Contacte To Admin");
                }
                
            }
            DB::commit();
            if($dd)
            {
                ActiveSafDetail::where('id',$saf_id)->delete();
                ActiveSafOwnerDetail::where('saf_dtl_id',$saf_id)->delete();
                ActiveSafFloorDetail::where('saf_dtl_id',$saf_id)->delete();
                ActiveSafTaxe::where('saf_dtl_id',$saf_id)->delete();
                return responseMsg(true,'Saf Forworded',["holding_no"=>$dd["holding_no"]]);
            }
           
            return responseMsg(true,'Saf Forworded','');
        }
        catch(Exception $e)
        {
            DB::rollBack();
            return responseMsg(false,$e->getMessage(),$request->all());
        }
        
   }

   # add workflow_tracks
   public function workflowTracks(array $inputs)
   { 
        try {
            $track = new WorkflowTrack();
            $track->workflow_candidate_id = $inputs['workflowCandidateID']??null;
            $track->citizen_id = $inputs['citizenID']??null;
            $track->module_id = $inputs['moduleID']??null;
            $track->ref_table_dot_id = $inputs['refTableDotID']??null;
            $track->ref_table_id_value = $inputs['refTableIDValue']??null;
            $track->message = $inputs['message']??null;
            $track->track_date = date('Y-m-d H:i:s');
            $track->forwarded_to = $inputs['forwardedTo']??null;
            $track->save();
            $message = ["status" => true, "message" => "Successfully Saved The Remarks", "data" => ''];
            return $message;
        } 
        catch (Exception $e) 
        {
            return  ['status'=>false,'message'=>$e->getMessage()];
        }
   } 
   public function updateWorkflowTracks(array $where,array $values)
   {
        try
        {
            WorkflowTrack::where($where)->update($values);
            return true;
        }
        catch (Exception $e) 
        {
            return  ['status'=>false,'message'=>$e->getMessage()];
        }
        
   }

   public function setWorkFlowForwordBackword(Request $request)
   {
    try{
        $rules = [
            "ulbID"=>"required",
            "workflowsID"=>"required"
        ];
        $validator = Validator::make($request->all(),$rules);
        if($validator->fails())
        {  
            return responseMsg(false,$validator->errors(),$request->all());
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
                        ->join("ulb_workflow_masters",function($join){
                                $join->on("workflows.id","=","ulb_workflow_masters.workflow_id");
                        })
                        ->join("ulb_workflow_roles",function($join){
                            $join->on("ulb_workflow_roles.ulb_workflow_id","=","ulb_workflow_masters.id")
                            ->whereNull("ulb_workflow_roles.deleted_at");
                        })
                        ->join("role_masters",function($join){
                            $join->on("role_masters.id","=","ulb_workflow_roles.role_id");
                        })
                        ->where("ulb_workflow_masters.ulb_id",$ulbID)
                        ->where("workflows.id",$workflowsID)
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
        if($request->getMethod()=="GET")
        {
            $mapping = 
            $response["rolls"] = $data;
            $response["initiator"] =$initiator;
            $response["finisher"] =$finisher;
            return responseMsg(true,'',remove_null($response));
        }
        elseif($request->getMethod()=="POST")
        {
            $rules = [
                "ulbID"         =>"required",
                "workflowsID"   =>"required",
                "initiator"      =>"required",
                "finisher"      =>"required",
                "rolles"       =>"required|array",
                "rolles.*.ID"   =>"required",
                "rolles.*.forwodID" =>"required",
                "rolles.*.backwodID" =>"required",
                "rolles.*.showFullList"=>"required|bool",
            ];
            $validator = Validator::make($request->all(),$rules);
            if($validator->fails())
            {  
                return responseMsg(false,$validator->errors(),$request->all());
            }
            $rolls = adjToArray($data);

            $data = array_map(function($val){
                return $val['rolle_id'];
            },adjToArray($data));
            $initiator = $request->initiator;
            $finisher = $request->finisher;
            $Rolles = $request->rolles; 
            foreach($Rolles as $key=>$val)
            {
                $id = $val['ID'];
                $roll_name = array_filter($rolls,function($val)use($id){
                    return $val['rolle_id']==$id;                   
                });
                $roll_name = array_values($roll_name)[0]??[];
                $Rolles[$key]["RollName"] = $roll_name["role_name"]??"Unknown User";
                $Rolles[$key]["ulb_workflow_roles_id"] = $roll_name["ulb_workflow_roles_id"]??0;
            }
            $message = array_map(function($val) use($initiator,$finisher,$data){
                if($val['ID']==$initiator && $val['backwodID'])
                    return "Initiator Has No Backword Id";
                elseif($val['ID']==$finisher && $val['forwodID'])
                    return "Finisher Has No Forwrod Id";
                elseif((!$val['backwodID'] || !$val['forwodID']) && !in_array($val['ID'],[$initiator,$finisher]))
                    return $val['RollName']." Have Forword And Backword Id ";
                elseif($val['ID'] == $val['forwodID'] || $val['ID'] == $val['forwodID'] )
                    return " Rolle ".$val['RollName']." Can't Forword And Backword Itself ";
                elseif(!in_array($val['ID'],$data))
                    return " Undefind Role Id ".$val['ID'];
            },$Rolles); 

            $message["initiator"] = array_filter($Rolles,function($val) use($initiator){
                if($val['ID']==$initiator)
                    return true ;
            });
            $message["finisher"] = array_filter($Rolles,function($val) use($finisher){
                if($val['ID']==$finisher)
                    return true ;
            });
            if(sizeof($message["finisher"])>1)
                $message["finisher"]="Finisher Id Is Multyple Time";
            if(!$message["finisher"])
                $message["finisher"]="Finisher Is Not Inclueded"; 
            else
                $message["finisher"] =[];       
            if(sizeof($message["initiator"])>1)
                $message["initiator"]="Initiator Id Is Multyple Time";           
            if(!$message["initiator"])
                $message["initiator"]="Initiator Is Not Inclueded"; 
                
            $mapping = array_map(function($val)use($Rolles){
                $ID = $val['ID'];
                $FID = $val['forwodID'];
                $BID = $val['backwodID']; 
                $RollName = $val['RollName']; 
                $m = array_map(function($v)use($ID,$FID,$BID, $RollName){                    
                    if($v['ID']==$FID && $v['backwodID']!=$ID)
                        return "$RollName Has No Proper Forword Id";
                    elseif($v['ID']==$BID && $v['forwodID']!=$ID)
                        return "$RollName Has No Proper Backword Id";
                },$Rolles);
                $m = array_filter($m,function($val){
                    return $val;
                });
                $m = array_values($m)[0]??[];
                if(!empty($m))
                    return ($m)  ;
            },$Rolles);
            if(is_array($message["initiator"]))
                $message["initiator"] = [];
            if(is_array($message["finisher"]))
                $message["finisher"] = [];
            $message = array_filter($message,function($val){return $val;});
            $message = array_values($message);
            $mapping = array_filter($mapping,function($val){return $val;});
            $mapping = array_values($mapping);

            if($mapping){
                foreach ($mapping as $key => $value) {
                    $message[]=$value;
                }
            }
            if($message)
            {
                return responseMsg(false,$message,$request->all());
            }
            DB::beginTransaction();
            $UlbWorkflowMaster=  UlbWorkflowMaster::where("workflow_id",$workflowsID)
                                ->where("ulb_id",$ulbID)
                                ->update(["initiator"=>$initiator,"finisher"=>$finisher]);
            foreach($Rolles as $val)
            { 
                $UlbWorkflowRoles = UlbWorkflowRole::find($val['ulb_workflow_roles_id']);
                if(!$UlbWorkflowRoles)
                { 
                    throw new Exception("Some Errors Please Contact To Admin");
                }
                $UlbWorkflowRoles->forward_id = $val['forwodID']?$val['forwodID']:null;
                $UlbWorkflowRoles->backward_id = $val['backwodID']?$val['backwodID']:null;
                $UlbWorkflowRoles->show_full_list = $val['showFullList']?$val['showFullList']:false;
                $UlbWorkflowRoles->save();

            }
            DB::commit();
            return responseMsg(true,"Workflow Memeber Add Succsessfully","");
        }
    }
    catch(Exception $e)
    {
        return responseMsg(false,$e->getMessage(),$request->all());
    }

   }
   
}
