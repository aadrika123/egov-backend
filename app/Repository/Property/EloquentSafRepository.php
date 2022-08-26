<?php

namespace App\Repository\Property;

use App\Repository\Property\SafRepository;
use Illuminate\Http\Request;
use App\Models\ActiveSafDetail;
use App\Models\ActiveSafFloorDetail;
use App\Models\ActiveSafOwnerDetail;
use App\Models\PropPropertie;
use App\Models\ObjectionTypeMstr;
use App\Models\PropertyObjection;
use App\Models\PropertyObjectionDetail;
use App\Models\PropFloorDetail;
use App\Models\PropOwner;
use App\Models\PropParamConstructionType;
use App\Models\PropParamFloorType;
use App\Models\PropParamOccupancyType;
use App\Models\PropParamPropertyType;
use App\Models\PropParamUsageType;
use App\Models\RoleMaster;
use App\Models\Saf;
use App\Models\UlbWorkflowMaster;
use App\Models\Ward\WardUser;
use App\Models\WardMstr;
use App\Models\WorkflowCandidate;
use App\Models\WorkflowTrack;

use App\Traits\Auth;
use App\Traits\Property\WardPermission;

use Exception;
use Illuminate\Support\Facades\Config;
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
        $message=["status"=>false,"data"=>$request->all(),"message"=>""];
        $user_id = auth()->user()->id;
        DB::beginTransaction();
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
                $message["message"]='Workflow Not Available';
                return response()->json($message,200);
            }
            $safNo = $this->safNo($request->ward,2,$user_id);
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
            $message=["status"=>true,"data"=>[],"message"=>"Successfully Submitted Your Application Your SAF No. $safNo"];
            return response()->json($message, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $e;
        }
    }

    /*
        #traits for comman function Inbox and Outbox of Saf
        * Created On : 11-08-2022 
        * Created by :Sandeep Bara
        #==================================================
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
    */
    public function safNo($ward_id,$assessment_type,$ulb_id)
    {
        $count = ActiveSafDetail::where('ward_mstr_id',$ward_id)
                                ->where('ulb_id',$ulb_id)
                                ->where('status',1)
                                ->count()+1;
        $ward_no = WardMstr::select("ward_no")->where('id',$ward_id)->first()->ward_no;        
        return $safNo = "SAF/".str_pad($assessment_type,2,'0',STR_PAD_LEFT)."/".str_pad($ward_no,3,'0',STR_PAD_LEFT)."/".str_pad($count,5,'0',STR_PAD_LEFT);
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
        $user_id = auth()->user()->id;
        $redis=Redis::connection();  // Redis Connection
        $redis_data = json_decode(Redis::get('user:' . $user_id),true);
        $ulb_id = $redis_data['ulb_id'];
        $roll_id =  $redis_data['role_id']; 
        $workflow_id = Config::get('workflow-constants.SAF_WORKFLOW_ID');        
        $work_flow_candidate = $this->work_flow_candidate($user_id,$ulb_id);
        if(!$work_flow_candidate)
        {
            $message=["status"=>false,"data"=>[],"message"=>"Your Are Not Authoried"];
            return response()->json($message,200);
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
                                                ward_mstrs.ward_no as ward_no,
                                                active_saf_details.created_at::date as apply_date") ,
                                       "active_saf_details.id",
                                       "active_saf_details.saf_no",
                                       "active_saf_details.id") 
                                       ->join('ward_mstrs', function($join){
                                            $join->on("ward_mstrs.id","=","active_saf_details.ward_mstr_id")
                                            ->where("ward_mstrs.status",1);
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
                                       ->whereIn('ward_mstr_id',$ward_ids) ;    
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
        $data=collect(['ulb_id'=>$ulb_id,
                        'user_id'=>$user_id,
                        'roll_id'=>$roll_id,
                        'workflow_id'=>$workflow_id,
                        'work_flow_candidate_id'=>$work_flow_candidate['id'],
                        'module_id'=>$work_flow_candidate['module_id'],
                        "data_list"=>$saf
                        ]
                        );
        return $data;
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
        $user_id = auth()->user()->id; 
        $redis=Redis::connection();  // Redis Connection
        $redis_data = json_decode(Redis::get('user:' . $user_id),true);
        $ulb_id = $redis_data['ulb_id'];
        $roll_id =  $redis_data['role_id']; 
        $workflow_id = Config::get('workflow-constants.SAF_WORKFLOW_ID');
        $work_flow_candidate = $this->work_flow_candidate($user_id,$ulb_id);
        if(!$work_flow_candidate)
        {
            $message=["status"=>false,"data"=>[],"message"=>"Your Are Not Authoried"];
            return response()->json($message,200);
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
                               'SAF' as assessment_type,
                                'VacentLand' as property_type,
                                ward_mstrs.ward_no as ward_no,
                                active_saf_details.created_at::date as apply_date") ,
                           "active_saf_details.id",
                           "active_saf_details.saf_no",
                           "active_saf_details.id") 
                           ->join('ward_mstrs', function($join){
                                $join->on("ward_mstrs.id","=","active_saf_details.ward_mstr_id")
                                ->where("ward_mstrs.status",1);
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
                           ->whereIn('ward_mstr_id',$ward_ids) ;
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
        $saf=$data->get()
        ->map(function($data) {
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
        $data=collect(['ulb_id'=>$ulb_id,
            'user_id'=>$user_id,
            'roll_id'=>$roll_id,
            'workflow_id'=>$workflow_id,
            'work_flow_candidate_id'=>$work_flow_candidate['id'],
            'module_id'=>$work_flow_candidate['module_id'],
            "data_list"=>$saf
            ]
        );
        return $data;
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
       $user_id = auth()->user()->id;
       $role_id = auth()->user()->roll_id;
       $ulb_id = auth()->user()->ulb_id;
       $saf_data = ActiveSafDetail::select(DB::raw("'VacantLand' as property_type,
                                                   'NewSaf' as assessment_type,
                                                   ward_mstrs.ward_no as ward_no,
                                                   active_saf_details.id as saf_id
                                                   "),
                                           "active_saf_details.*"
                                           )
                                           ->join('ward_mstrs', function($join){
                                                $join->on("ward_mstrs.id","=","active_saf_details.ward_mstr_id")
                                                ->where("ward_mstrs.status",1);
                                            })
                                           ->where('active_saf_details.id',"=",$saf_id)                                            
                                           ->first();
       $data= remove_null($saf_data);
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
        return $data;
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
            $messages = ["status"=>false,"data"=>$request->all(),"message"=>$validator->errors()];
            return response()->json($messages,200);
        }

        $user_id = auth()->user()->id;
        $saf = new ActiveSafDetail;
        
        $saf_id = $request->id??$request->safId;
        $data = $saf->where('current_user',$user_id)->find($saf_id);
        if(!$data)
        {
            $message=["status"=>false,"data"=>$request->all(),"message"=>"Saf Not Found"];
            return response()->json($message,200);
        }
        DB::beginTransaction();
        $data->is_escalate=$request->escalateStatus;  
        $data->escalate_by=$user_id;        
        $data->save();
        DB::commit();
        $messages = ["status"=>true,"data"=>[],"message"=>($request->escalateStatus==1?'Saf is Escalated':"Saf is removed from Escalated")];
        return response()->json($messages,200);
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

        $user_id = auth()->user()->id;
        $redis=Redis::connection();  // Redis Connection
        $redis_data = json_decode(Redis::get('user:' . $user_id),true);
        $ulb_id = $redis_data['ulb_id'];
        $roll_id =  $redis_data['role_id']; 
        $workflow_id = Config::get('workflow-constants.SAF_WORKFLOW_ID');
        $work_flow_candidate = $this->work_flow_candidate($user_id,$ulb_id);
        if(!$work_flow_candidate)
        {
            $message=["status"=>false,"data"=>[],"message"=>"Your Are Not Authoried"];
            return response()->json($message,200);
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
                                                ward_mstrs.ward_no as ward_no,
                                                active_saf_details.created_at::date as apply_date") ,
                                        "active_saf_details.id",
                                        "active_saf_details.saf_no",
                                        "active_saf_details.id") 
                                        ->join('ward_mstrs', function($join){
                                            $join->on("ward_mstrs.id","=","active_saf_details.ward_mstr_id")
                                            ->where("ward_mstrs.status",1);
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
                                        ->whereIn('ward_mstr_id',$ward_ids) ;
       
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
        $data=collect(['ulb_id'=>$ulb_id,
            'user_id'=>$user_id,
            'roll_id'=>$roll_id,
            'workflow_id'=>$workflow_id,
            'work_flow_candidate_id'=>$work_flow_candidate['id'],
            'module_id'=>$work_flow_candidate['module_id'],
            "data_list"=>$saf
            ]
        );
        return $data;
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
     
        $messages = ["status"=>false,"data"=>$request->all(),"message"=>''];    
        try{
            $user_id = auth()->user()->id;
            $saf = new ActiveSafDetail;
            $saf_id = $request->id??$request->safId;
            $data = $saf->where('current_user',$user_id)->find($saf_id);  
            if(!$data)
            {
                $message=["status"=>false,"data"=>$request->all(),"message"=>"Saf Not Found"];
                return response()->json($message,200);
            }
            $regex = '/^[a-zA-Z1-9][a-zA-Z1-9\\s]+$/';
            $rules=[
                "ulbId"=>"required",
                "receiverId"=>"required|int",
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
                $messages["message"] = $validator->errors();
                return response()->json($messages,200);
            }            
            $work_flow_candidate = WorkflowCandidate::select('workflow_candidates.id',"ulb_workflow_masters.module_id")
                                    ->join('ulb_workflow_masters','ulb_workflow_masters.id','workflow_candidates.ulb_workflow_id')
                                    ->where('workflow_candidates.user_id',$user_id)
                                    ->where('ulb_workflow_masters.ulb_id',$request->ulbId)
                                    ->first();
            if(!$work_flow_candidate)
            {
                $messages["message"] = "work_flow_candidate not found";
                return response()->json($messages,200);
            }
            DB::beginTransaction();
            $data->current_user=$request->receiverId;
            $data->save();
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
                DB::rollBack();
                $messages["message"] = $workfloes['message'];
                return response()->json($messages,200);
            }
            DB::commit();
            $messages = ["status"=>true,"data"=>[],"message"=>'Saf Forworded'];
            return response()->json($messages,200);
        }
        catch(Exception $e)
        {
            return response()->json($e, 400);
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
        } catch (Exception $e) {
            return  ['status'=>false,'message'=>$e->getMessage()];
        }
   }   
}
