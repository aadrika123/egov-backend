<?php

namespace App\Repository\Property\Concrete;

use App\EloquentClass\Property\InsertTax;
use App\EloquentClass\Property\SafCalculation;
use App\EloquentModels\Common\ModelWard;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsFloor;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Property\PropFloor;
use App\Models\Property\PropLevelPending;
use App\Models\Workflows\WfWorkflow;
use App\Repository\Common\CommonFunction;
use App\Repository\Property\Interfaces\IPropertyBifurcation;
use App\Traits\Auth;
use App\Traits\Helper;
use App\Traits\Payment\Razorpay;
use App\Traits\Property\SAF;
use App\Traits\Property\WardPermission;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PropertyBifurcation implements IPropertyBifurcation
{

    use Auth;                                                               // Trait Used added by sandeep bara date 17-08-2022
    use WardPermission;
    use Workflow;
    use SAF;
    use Razorpay;
    use Helper;

    protected $_common;
    protected $_modelWard;
    protected $_Saf;
    protected $_property;
    public function __construct()
    {
        $this->_common = new CommonFunction();
        $this->_modelWard = new ModelWard();
        $this->_Saf = new SafRepository();
        $this->_property = new PropertyDeactivate();
    }
    public function addRecord(Request $request)
    {
        try{
            $refUser    = Auth()->user();
            $refUserId  = $refUser->id;
            $refUlbId   = $refUser->ulb_id;
            $mProperty  = $this->_property->getPropertyById($request->id);
            $mNowDate   = Carbon::now()->format("Y-m-d");
            $mNowDateYm   = Carbon::now()->format("Y-m");
            $refWorkflowId = Config::get('workflow-constants.SAF_BIFURCATION_ID');            
            $mUserType  = $this->_common->userType($refWorkflowId);
            $init_finish = $this->_common->iniatorFinisher($refUserId,$refUlbId,$refWorkflowId); 
            if(!$init_finish)
            {
                throw new Exception("Full Work Flow Not Desigen Properly. Please Contact Admin !!!...");
            }
            elseif(!$init_finish["initiator"])
            {
                throw new Exception("Initiar Not Available. Please Contact Admin !!!...");
            }
            if(!$mProperty)
            {
                throw new Exception("Property Not Found");
            }
              
            $priv_data = PropActiveSaf::select("*")
                        ->where("previous_holding_id",$mProperty->id)
                        ->orderBy("id","desc")
                        ->first(); 
            if($priv_data)
            {
                throw new Exception("Assesment already apply");
            } 
            $mOwrners  = $this->_property->getPropOwnerByProId($mProperty->id);
            $mFloors    = $this->getFlooreDtl($mProperty->id);
            if($request->getMethod()=="GET")
            {
                $data = [
                    "property"=>$mProperty,
                    "owners"    => $mOwrners,
                    "floors"   => $mFloors,
                ];
                return responseMsg(true,'',remove_null($data));
            }
            elseif($request->getMethod()=="POST")
            {
                $assessmentTypeId = $request->assessmentType ;                
                $ulbWorkflowId = WfWorkflow::where('wf_master_id', $refWorkflowId)
                    ->where('ulb_id', $refUlbId)
                    ->first(); 
                DB::beginTransaction();
                $safNo = [];
                $parentSaf="";
                foreach($request->container as $key=>$val)
                {
                    $myRequest = new \Illuminate\Http\Request();
                    $myRequest->setMethod('POST');
                    $myRequest->request->add(['assessmentType' => $assessmentTypeId]);
                    foreach($val as $key2 =>$val2)
                    {
                        $myRequest->request->add([$key2 => $val2]);
                    }
                    $safNo[$key] = $this->insertData($myRequest);
                    if($myRequest->isAcquired)
                    {
                        $parentSaf = $safNo[$key];
                    }
                    
                }
               $safNo = $parentSaf;
                DB::commit();
                return responseMsg(true, "Successfully Submitted Your Application Your SAF No. $safNo", ["safNo" => $safNo]);
            }
        }
        catch(Exception $e)
        {
            DB::rollBack();
            return responseMsg(false,$e->getMessage(),$request->all());
        }
    }
    public function inbox(Request $request)
    {
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refUlbId       = $refUser->ulb_id;
            $refWorkflowId = Config::get('workflow-constants.SAF_BIFURCATION_ID'); 
            $refWorkflowMstrId     = WfWorkflow::where('wf_master_id', $refWorkflowId)
                                    ->where('ulb_id', $refUlbId)
                                    ->first();
            if (!$refWorkflowMstrId) 
            {
                throw new Exception("Workflow Not Available");
            }
            
            $mUserType = $this->_common->userType($refWorkflowId);
            $mWardPermission = $this->_common->WardPermission($refUserId);           
            $mRole = $this->_common->getUserRoll($refUserId,$refUlbId,$refWorkflowMstrId->wf_master_id);
            $mJoins ="";
            if (!$mRole) 
            {
                throw new Exception("You Are Not Authorized For This Action");
            } 
            if($mRole->is_initiator )    
            {
                $mWardPermission = $this->_modelWard->getAllWard($refUlbId)->map(function($val){
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $mWardPermission = objToArray($mWardPermission);
                $mJoins = "leftjoin";
            }
            else
            {
                $mJoins = "join";
            }

            $mWardIds = array_map(function ($val) {
                return $val['id'];
            }, $mWardPermission);

            $mRoleId = $mRole->role_id;   
            $inputs = $request->all();
            // DB::enableQueryLog();          
            $application = PropActiveSaf::select('prop_active_safs.saf_no',
                                            'prop_active_safs.id',
                                            'prop_active_safs.ward_mstr_id',
                                            'prop_active_safs.prop_type_mstr_id',
                                            'prop_active_safs.appartment_name',
                                            'ref_prop_types.property_type',
                                            'prop_active_safs.assessment_type',
                                            "owner.owner_name",
                                            "owner.guardian_name",
                                            "owner.mobile_no",
                                            "owner.email_id",
                                            DB::raw("prop_level_pendings.id AS level_id, 
                                                    ward.ward_name as ward_no,
                                                    at.assessment_type as assessment"
                                                    )
                                            )
                        ->join("ref_prop_types","ref_prop_types.id","prop_active_safs.prop_type_mstr_id")
                        ->join('ulb_ward_masters as ward', 'ward.id', '=', 'prop_active_safs.ward_mstr_id')
                        ->join('prop_ref_assessment_types as at', 'at.id', '=', 'prop_active_safs.assessment_type')
                        ->$mJoins("prop_level_pendings",function($join) use($mRoleId){
                                $join->on("prop_level_pendings.saf_id","prop_active_safs.id")
                                ->where("prop_level_pendings.receiver_role_id",$mRoleId)
                                ->where("prop_level_pendings.status",1)
                                ->where("prop_level_pendings.verification_status",0);
                        })
                        ->join(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                            STRING_AGG(guardian_name,',') AS guardian_name,
                                            STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                            STRING_AGG(email,',') AS email_id,
                                            saf_id
                                        FROM prop_active_safs_owners 
                                        WHERE status =1
                                        GROUP BY saf_id
                                        )owner"),function($join){
                                            $join->on("owner.saf_id","prop_active_safs.id");
                                        })
                        ->where("prop_active_safs.status",1)                        
                        ->where("prop_active_safs.ulb_id",$refUlbId);
            if(isset($inputs['key']) && trim($inputs['key']))
            {
                $key = trim($inputs['key']);
                $application = $application->where(function ($query) use ($key) {
                    $query->orwhere('prop_active_safs.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('prop_active_safs.saf_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere("prop_active_safs.provisional_license_no", 'ILIKE', '%' . $key . '%')                                            
                        ->orwhere('owner.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.guardian_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            if(isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo']!="ALL")
            {
                $mWardIds =$inputs['wardNo']; 
            }
            if(isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate'])
            {
                $application = $application
                            ->whereBetween('prop_level_pendings.created_at::date',[$inputs['formDate'],$inputs['formDate']]); 
            }
            if($mRole->is_initiator)
            {
                $application = $application->whereIn('prop_active_safs.saf_pending_status',[0,2]);
            }
            else
            {
                $application = $application->whereIn('prop_active_safs.saf_pending_status',[3]);
            }            
            $application = $application
                    ->where("prop_active_safs.workflow_id",$refWorkflowMstrId->id)
                    ->where("prop_active_safs.isAcquired",true)
                    ->whereIn('prop_active_safs.ward_mstr_id', $mWardIds)
                    ->get();
            // dd(DB::getQueryLog());
            $data = [
                "userType"      => $mUserType,
                "wardList"      =>  $mWardPermission,                
                "applications"  =>  $application,
            ] ;           
            return responseMsg(true, "", $data);
        }
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }

    }
    public function outbox(Request $request)
    {
        try {
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $refWorkflowId = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
            $workflowId = WfWorkflow::where('wf_master_id', $refWorkflowId)
                ->where('ulb_id', $ulb_id)
                ->first();
            if (!$workflowId) 
            {
                throw new Exception("Workflow Not Available");
            }
            $mUserType = $this->_parent->userType($refWorkflowId);
            $ward_permission = $this->_parent->WardPermission($user_id);
            $role = $this->_parent->getUserRoll($user_id,$ulb_id,$workflowId->wf_master_id);           
            if (!$role) 
            {
                throw new Exception("You Are Not Authorized");
            }
            if($role->is_initiator || in_array(strtoupper($mUserType),["JSK","SUPER ADMIN","ADMIN","TL","PMU","PM"]))
            {
                $joins = "leftjoin";
                $ward_permission = $this->_modelWard->getAllWard($ulb_id)->map(function($val){
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $ward_permission = objToArray($ward_permission);
            }
            else
            {
                $joins = "join";
            }
            $role_id = $role->role_id;

            $ward_ids = array_map(function ($val) {
                return $val['id'];
            }, $ward_permission);
            $inputs = $request->all();
            // DB::enableQueryLog();
            $application = PropActiveSaf::select('prop_active_safs.saf_no',
                                            'prop_active_safs.id',
                                            'prop_active_safs.ward_mstr_id',
                                            'prop_active_safs.prop_type_mstr_id',
                                            'prop_active_safs.appartment_name',
                                            'ref_prop_types.property_type',
                                            'prop_active_safs.assessment_type',
                                            "owner.owner_name",
                                            "owner.guardian_name",
                                            "owner.mobile_no",
                                            "owner.email_id",
                                            DB::raw("ward.ward_name as ward_no,
                                                at.assessment_type as assessment"
                                                )
                                            )
                        ->join("ref_prop_types","ref_prop_types.id","prop_active_safs.prop_type_mstr_id")
                        ->join('ulb_ward_masters as ward', 'ward.id', '=', 'prop_active_safs.ward_mstr_id')
                        ->join('prop_ref_assessment_types as at', 'at.id', '=', 'prop_active_safs.assessment_type')
                        ->$joins("prop_level_pendings",function($join) use($role_id){
                            $join->on("prop_level_pendings.saf_id","prop_active_safs.id")
                            ->where("prop_level_pendings.sender_role_id",$role_id)
                            ->where("prop_level_pendings.status",1)
                            ->where("prop_level_pendings.verification_status",0);
                        })
                        ->join(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                            STRING_AGG(guardian_name,',') AS guardian_name,
                                            STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                            STRING_AGG(email,',') AS email_id,
                                            saf_id
                                        FROM prop_active_safs_owners 
                                        WHERE status =1
                                        GROUP BY saf_id
                                        )owner"),function($join){
                                            $join->on("owner.saf_id","prop_active_safs.id");
                                        })
                        ->where("prop_active_safs.status",1)                        
                        ->where("prop_active_safs.ulb_id",$ulb_id);
            
            if(isset($inputs['key']) && trim($inputs['key']))
            {
                $key = trim($inputs['key']);
                $application = $application->where(function ($query) use ($key) {
                    $query->orwhere('prop_active_safs.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('prop_active_safs.saf_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere("prop_active_safs.provisional_license_no", 'ILIKE', '%' . $key . '%')                                            
                        ->orwhere('owner.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.guardian_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('owner.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            
            if(isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo']!="ALL")
            {
                $ward_ids =$inputs['wardNo']; 
            }
            if(isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate'])
            {
                $application = $application
                            ->whereBetween('prop_level_pendings.created_at::date',[$inputs['formDate'],$inputs['formDate']]); 
            }
            if(!$role->is_initiator)
            {
                $application = $application->whereIn('prop_active_safs.pending_status',[3]);
            }
            else
            {
                $application = $application->whereIn('prop_active_safs.pending_status',[2,3]);
            }
            $application = $application
                        ->where("prop_active_safs.workflow_id",$workflowId->id)
                        ->where("prop_active_safs.isAcquired",true)
                        ->whereIn('prop_active_safs.ward_mstr_id', $ward_ids)
                        ->get();
            // dd(DB::getQueryLog());
            $data = [
                "userType"      => $mUserType,
                "wardList"=>$ward_permission,                
                "application"=>$application,
            ] ; 
            return responseMsg(true, "", $data);
            
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    #------------------------------CORE Function ---------------------------------------------
    public function getFlooreDtl($propertyId)
    {
        try{
            $mFloors = PropFloor::select("*")
                        ->where("status",1)
                        ->where("property_id",$propertyId)
                        ->get();
            return $mFloors;
        }
        catch(Exception $e)
        {
            return [];
        }
    }

    public function insertData(Request $req)
    {
        try{
            $refUser    = Auth()->user();
            $refUserId  = $refUser->id;
            $refUlbId   = $refUser->ulb_id;
            $mNowDate   = Carbon::now()->format("Y-m-d");
            $mNowDateYm   = Carbon::now()->format("Y-m");
            $refWorkflowId = Config::get('workflow-constants.SAF_BIFURCATION_ID');            
            $mUserType  = $this->_common->userType($refWorkflowId);
            $init_finish = $this->_common->iniatorFinisher($refUserId,$refUlbId,$refWorkflowId);

            $assessmentTypeId = $req->assessmentType ;                
            $ulbWorkflowId = WfWorkflow::where('wf_master_id', $refWorkflowId)
                ->where('ulb_id', $refUlbId)
                ->first();
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

            $refInitiatorRoleId = $init_finish["initiator"]['id'];                // Get Current Initiator ID
            $initiatorRoleId = $refInitiatorRoleId;
            // dd($request->ward);
            $safNo = $this->safNo($req->ward, $assessmentTypeId, $refUlbId);
            $saf = new PropActiveSaf();
            
            // workflows
            $saf->user_id       = $refUserId;
            $saf->workflow_id   = $ulbWorkflowId->id;
            $saf->ulb_id        = $refUlbId;
            $saf->isAcquired    = $req->isAcquired;
            $saf->current_role = $initiatorRoleId;
            $saf->save();
            $safNo = $safNo."/".$saf->id;
            $this->tApplySaf($saf, $req, $safNo, $assessmentTypeId);                    // Trait SAF Apply
            $saf->update(); 
            // SAF Owner Details
            if ($req['owner']) 
            {
                $owner_detail = $req['owner'];
                foreach ($owner_detail as $owner_details) 
                {
                    $owner = new PropActiveSafsOwner();
                    $this->tApplySafOwner($owner, $saf, $owner_details);                    // Trait Owner Details
                    $owner->save();
                }
            }

            // Floor Details
            if ($req['floor']) 
            {
                $floor_detail = $req['floor'];
                foreach ($floor_detail as $floor_details) 
                {
                    $floor = new PropActiveSafsFloor();
                    $this->tApplySafFloor($floor, $saf, $floor_details);
                    $floor->save();
                }
            }
            // Property SAF Label Pendings
            // $labelPending = new PropLevelPending();
            // $labelPending->saf_id = $saf->id;
            // $labelPending->receiver_role_id = $initiatorRoleId;
            // $labelPending->save();
            // Insert Tax
            $tax = new InsertTax();
            $tax->insertTax($saf->id, $refUserId, $safTaxes);                                         // Insert SAF Tax
            return $safNo;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
}