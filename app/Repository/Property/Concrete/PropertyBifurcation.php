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
                // $assessmentTypeId = Config::get("PropertyConstaint.ASSESSMENT-TYPE.3");                
                $ulbWorkflowId = WfWorkflow::where('wf_master_id', $refWorkflowId)
                    ->where('ulb_id', $refUlbId)
                    ->first();
    
                if ($request->roadType <= 0)
                    $request->roadType = 4;
                elseif ($request->roadType > 0 && $request->roadType < 20)
                    $request->roadType = 3;
                elseif ($request->roadType >= 20 && $request->roadType <= 39)
                    $request->roadType = 2;
                elseif ($request->roadType > 40)
                    $request->roadType = 1;
    
                $safCalculation = new SafCalculation();
                $safTaxes = $safCalculation->calculateTax($request);
    
                $refInitiatorRoleId = $init_finish["initiator"]['id'];                // Get Current Initiator ID
                $initiatorRoleId = $refInitiatorRoleId;

                DB::beginTransaction();
                // dd($request->ward);
                $safNo = $this->safNo($request->ward, $assessmentTypeId, $refUlbId);
                $saf = new PropActiveSaf();
                $this->tApplySaf($saf, $request, $safNo, $assessmentTypeId);                    // Trait SAF Apply
                // workflows
                $saf->user_id = $refUserId;
                $saf->workflow_id = $ulbWorkflowId->id;
                $saf->ulb_id = $refUlbId;
                $saf->current_role = $initiatorRoleId;
                $saf->save();
    
                // SAF Owner Details
                if ($request['owner']) 
                {
                    $owner_detail = $request['owner'];
                    foreach ($owner_detail as $owner_details) 
                    {
                        $owner = new PropActiveSafsOwner();
                        $this->tApplySafOwner($owner, $saf, $owner_details);                    // Trait Owner Details
                        $owner->save();
                    }
                }
    
                // Floor Details
                if ($request['floor']) 
                {
                    $floor_detail = $request['floor'];
                    foreach ($floor_detail as $floor_details) 
                    {
                        $floor = new PropActiveSafsFloor();
                        $this->tApplySafFloor($floor, $saf, $floor_details);
                        $floor->save();
                    }
                }
                dd($safTaxes->original);
                // Property SAF Label Pendings
                $labelPending = new PropLevelPending();
                $labelPending->saf_id = $saf->id;
                $labelPending->receiver_role_id = $initiatorRoleId;
                $labelPending->save();
                // Insert Tax
                $tax = new InsertTax();
                $tax->insertTax($saf->id, $refUserId, $safTaxes);                                         // Insert SAF Tax
    
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
}