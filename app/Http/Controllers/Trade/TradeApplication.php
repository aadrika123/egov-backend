<?php

namespace App\Http\Controllers\Trade;

use App\Http\Controllers\Controller;
use App\EloquentModels\Common\ModelWard;
use App\Repository\Common\CommonFunction;
use App\Repository\Trade\ITrade;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use App\Http\Requests\Trade\ReqAddRecorde;
use App\Http\Requests\Trade\paymentCounter;
use App\Http\Requests\Trade\ReqApplyDenail;
use App\Http\Requests\Trade\ReqPaybleAmount;
use App\Http\Requests\Trade\ReqInbox;
use App\Http\Requests\Trade\ReqPostNextLevel;
use App\Http\Requests\Trade\ReqUpdateBasicDtl;
use App\Models\Workflows\WfWorkflow;
use Exception;

class TradeApplication extends Controller
{
    
    /**
     * | Created On-01-10-2022 
     * | Created By-Sandeep Bara
     * --------------------------------------------------------------------------------------
     * | Controller regarding with Trade Module
     */

    // Initializing function for Repository
    private $Repository;
    private $_modelWard;
    private $_parent;
    public function __construct(ITrade $TradeRepository)
    {
        $this->Repository = $TradeRepository ;
        $this->_modelWard = new ModelWard();
        $this->_parent = new CommonFunction();
    }
    # Serial No : 01
    public function applyApplication(ReqAddRecorde $request)
    { 
        $refUser            = Auth()->user(); 
        $refUserId          = $refUser->id;
        $refUlbId           = $refUser->ulb_id;
        if($refUser->user_type==Config::get("TradeConstant.CITIZEN"))
        {
            $refUlbId = $request->ulbId??0;
        }
        $refWorkflowId      = Config::get('workflow-constants.TRADE_WORKFLOW_ID'); 
        $mUserType          = $this->_parent->userType($refWorkflowId);
        $refWorkflows       = $this->_parent->iniatorFinisher($refUserId,$refUlbId,$refWorkflowId);        
        $mApplicationTypeId = Config::get("TradeConstant.APPLICATION-TYPE.".$request->applicationType);
        try{     
            if(!in_array(strtoupper($mUserType),["ONLINE","JSK","UTC","TC","SUPER ADMIN","TL"]))
            {
                throw new Exception("You Are Not Authorized For This Action !");
            }            
            if(!$mApplicationTypeId)
            {
                throw new Exception("Invalide Application Type");
            }
            if (!$refWorkflows) 
            {
                throw new Exception("Workflow Not Available");
            } 
            if(!$refWorkflows['initiator'])
            {
                throw new Exception("Initiator Not Available"); 
            }
            if(!$refWorkflows['finisher'])
            {
                throw new Exception("Finisher Not Available"); 
            }
            if (in_array($mApplicationTypeId, ["2", "3","4"]) && (!$request->id || !is_numeric($request->id))) 
            {
                throw new Exception ("Old licence Id Requird");
            }  
            return $this->Repository->addRecord($request);
        }   
        catch(Exception $e)
        { 
            return responseMsg(false,$e->getMessage(),$request->all());
        } 
    }
    public function paymentCounter(paymentCounter $request)
    {
        return $this->Repository->paymentCounter($request);
    }
    # Serial No : 02
    public function updateLicenseBo(ReqUpdateBasicDtl $request)
    {
        return $this->Repository->updateLicenseBo($request);
    }
    
    public function updateBasicDtl(ReqUpdateBasicDtl $request)
    {
        return $this->Repository->updateBasicDtl($request);
    }    
    # Serial No : 04
    public function paymentReceipt(Request $request)
    {
        $id = $request->id;
        $transectionId =  $request->transectionId;
        return $this->Repository->readPaymentReceipt($id,$transectionId);
    }
    # Serial No : 05
    public function documentUpload(Request $request)
    {
        return $this->Repository->documentUpload($request);
    }
     # Serial No : 06
    public function getUploadDocuments(Request $request)
    {
        return $this->Repository->getUploadDocuments($request);
    }
     # Serial No : 07
    public function documentVirify(Request $request)
    {
        return $this->Repository->documentVirify($request);
    }
    # Serial No : 08 
    public function getLicenceDtl(Request $request)
    {
        return $this->Repository->readLicenceDtl($request->id);
    }
    # Serial No : 09 
    public function getDenialDetails(Request $request)
    {
        return $this->Repository->readDenialdtlbyNoticno($request);
    }
     # Serial No : 10 
    public function paybleAmount(ReqPaybleAmount $request)
    {      
        return $this->Repository->getPaybleAmount($request);
    }

    # Serial No : 12 
    public function validateHoldingNo(Request $request)
    {
        return $this->Repository->isvalidateHolding($request);
    }
    # Serial No : 13 
    public function searchLicence(Request $request)
    {
        return $this->Repository->searchLicenceByNo($request);
    }
    # Serial No : 14
    public function readApplication(Request $request)
    {
        return $this->Repository->readApplication($request);
    }
    # Serial No : 15
    public function postEscalate(Request $request)
    {
        return $this->Repository->postEscalate($request);
    }
    public function specialInbox(Request $request)
    {
        return $this->Repository->specialInbox($request);
    }
    public function btcInbox(Request $request)
    {
        return $this->Repository->btcInbox($request);
    }
    # Serial No : 16
    public function inbox(ReqInbox $request)
    {
        return $this->Repository->inbox($request);
    }
    # Serial No : 17
    public function outbox(Request $request)
    {
        return $this->Repository->outbox($request);
    }
    # Serial No : 18
    public function postNextLevel(ReqPostNextLevel $request)
    {
        try{ 
            $refUser = Auth()->user();
            $user_id = $refUser->id;
            $ulb_id = $refUser->ulb_id;
            $refWorkflowId = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
            $init_finish = $this->_parent->iniatorFinisher($user_id,$ulb_id,$refWorkflowId);
            if(!$init_finish)
            {
                throw new Exception("Full Work Flow Not Desigen Properly. Please Contact Admin !!!...");
            }
            if(!$init_finish["initiator"])
            {
                throw new Exception("Initiar Not Available. Please Contact Admin !!!...");
            }
            if(!$init_finish["finisher"])
            {
                throw new Exception("Finisher Not Available. Please Contact Admin !!!...");
            }
            return $this->Repository->postNextLevel($request);
        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }       
    }
    # Serial No : 19
    public function provisionalCertificate(Request $request)
    {
        return $this->Repository->provisionalCertificate($request->id);
    }
    # Serial No : 20
    public function licenceCertificate(Request $request)
    {
        return $this->Repository->licenceCertificate($request->id);
    }
    # Serial No : 21
    public function applyDenail(ReqApplyDenail $request)
    {
        try{
            $user = Auth()->user();
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $refWorkflowId = Config::get('workflow-constants.TRADE_NOTICE_ID');
            $workflowId = WfWorkflow::where('wf_master_id', $refWorkflowId)
                    ->where('ulb_id', $ulbId)
                    ->first();
            if (!$workflowId) 
            {
                throw new Exception("Workflow Not Available");
            }
            $role = $this->_parent->getUserRoll($userId,$ulbId,$workflowId->wf_master_id); 
            if (!$role) 
            {
                throw new Exception("You Are Not Authorized");
            }
            $userType = $this->_parent->userType($refWorkflowId);
            if(!in_array(strtoupper($userType),["TC","UTC"]))
            {
                throw new Exception("You Are Not Authorize For Apply Denial");
            }
            if($request->getMethod()=='GET')
            {
                $data['wardList'] = $this->_parent->WardPermission($userId);
                return  responseMsg(true,"",$data);
            }
            return $this->Repository->addDenail($request);
        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }        
    }
    # Serial No : 22
    public function addIndependentComment(Request $request)
    {
        return $this->Repository->addIndependentComment($request);
    }
    # Serial No : 23
    public function readIndipendentComment(Request $request)
    {
        return $this->Repository->readIndipendentComment($request);
    }
    # Serial No : 24
    public function denialInbox(Request $request)
    {
        try{
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $workflow_id = Config::get('workflow-constants.TRADE_NOTICE_ID');
            $role = $this->_parent->getUserRoll($user_id, $ulb_id,$workflow_id) ;
            $role_id = $role->role_id??-1;
            if( !$role  || !in_array($role_id,[10]))
            {
                throw new Exception("You Are Not Authorized");
            }
            return $this->Repository->denialInbox($request);
        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
        
    }
    # Serial No : 25
    public function denialview(Request $request)
    {
        $id = $request->id;
        $mailID = $request->mailID;
        return $this->Repository->denialView($id,$mailID,$request);
    }
    # Serial No : 26
    public function approvedApplication(Request $request)
    {
        return $this->Repository->approvedApplication($request);
    }
    
    
    public function reports(Request $request)
    {
        return $this->Repository->reports($request);
    }    
    
}