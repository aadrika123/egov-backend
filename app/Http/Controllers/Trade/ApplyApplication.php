<?php

namespace App\Http\Controllers\Trade;

use App\Http\Controllers\Controller;
use App\EloquentModels\Common\ModelWard;
use App\Repository\Common\CommonFunction;
use App\Repository\Trade\ITrade;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use App\Http\Requests\Trade\addRecorde;
use App\Http\Requests\Trade\paymentCounter;
use App\Http\Requests\Trade\reqPaybleAmount;
use App\Http\Requests\Trade\reqInbox;
use App\Http\Requests\Trade\requpdateBasicDtl;
use Exception;

class ApplyApplication extends Controller
{

    /**
     * | Created On-01-10-2022 
     * | Created By-Sandeep Bara
     * --------------------------------------------------------------------------------------
     * | Controller regarding with Trade Module
     */

    // Initializing function for Repository
    private $Repository;
    protected $_modelWard;
    protected $_parent;
    public function __construct(ITrade $TradeRepository)
    {
        $this->Repository = $TradeRepository ;
        $this->_modelWard = new ModelWard();
        $this->_parent = new CommonFunction();
    }
    public function applyApplication(addRecorde $request)
    {   
        $refUser            = Auth()->user();
        $refUserId          = $refUser->id;
        $refUlbId           = $refUser->ulb_id;
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
            if (in_array($mApplicationTypeId, ["2", "3","4"]) && !$request->id) 
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
    public function paybleAmount(reqPaybleAmount $request)
    {      
        return $this->Repository->getPaybleAmount($request);
    }
    public function validateHoldingNo(Request $request)
    {
        return $this->Repository->isvalidateHolding($request);
    }
    public function paymentRecipt(Request $request)
    {
        $id = $request->id;
        $transectionId =  $request->transectionId;
        return $this->Repository->readPaymentRecipt($id,$transectionId);
    }
    public function updateLicenseBo(requpdateBasicDtl $request)
    {
        return $this->Repository->updateLicenseBo($request);
    }
    public function updateBasicDtl(requpdateBasicDtl $request)
    {
        return $this->Repository->updateBasicDtl($request);
    }
    public function documentUpload(Request $request)
    {
        return $this->Repository->documentUpload($request);
    }
    public function getUploadDocuments(Request $request)
    {
        return $this->Repository->getUploadDocuments($request);
    }
    
    public function documentVirify(Request $request)
    {
        return $this->Repository->documentVirify($request);
    }
    public function getLicenceDtl(Request $request)
    {
        return $this->Repository->readLicenceDtl($request->id);
    }
    public function getDenialDetails(Request $request)
    {
        return $this->Repository->readDenialdtlbyNoticno($request);
    }
    public function searchLicence(Request $request)
    {
        return $this->Repository->searchLicenceByNo($request);
    }
    public function readApplication(Request $request)
    {
        return $this->Repository->readApplication($request);
    }
    public function postEscalate(Request $request)
    {
        return $this->Repository->postEscalate($request);
    }
    public function inbox(reqInbox $request)
    {
        return $this->Repository->inbox($request);
    }
    public function outbox(Request $request)
    {
        return $this->Repository->outbox($request);
    }
    public function postNextLevel(Request $request)
    {
        return $this->Repository->postNextLevel($request);
    }
    public function addIndependentComment(Request $request)
    {
        return $this->Repository->addIndependentComment($request);
    }
    public function readIndipendentComment(Request $request)
    {
        return $this->Repository->readIndipendentComment($request);
    }
    public function paymentCounter(paymentCounter $request)
    {
        return $this->Repository->paymentCounter($request);
    }
    public function handeRazorPay(Request $request)
    {
        return $this->Repository->handeRazorPay($request);
    }
    public function provisionalCertificate(Request $request)
    {
        return $this->Repository->provisionalCertificate($request->id);
    }
    public function licenceCertificate(Request $request)
    {
        return $this->Repository->licenceCertificate($request->id);
    }
    public function applyDenail(Request $request)
    {
        return $this->Repository->addDenail($request);
    }
    public function denialInbox(Request $request)
    {
        return $this->Repository->denialInbox($request);
    }
    public function denialview(Request $request)
    {
        $id = $request->id;
        $mailID = $request->mailID;
        return $this->Repository->denialView($id,$mailID,$request);
    }
    public function approvedApplication(Request $request)
    {
        return $this->Repository->approvedApplication($request);
    }
    public function reports(Request $request)
    {
        return $this->Repository->reports($request);
    }
    public function citizenApplication(Request $request)
    {
        return $this->Repository->citizenApplication();
    }
    public function readCitizenLicenceDtl($id)
    {
        return $this->Repository->readCitizenLicenceDtl($id);
    }
}