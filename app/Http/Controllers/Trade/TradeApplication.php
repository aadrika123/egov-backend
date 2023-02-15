<?php

namespace App\Http\Controllers\Trade;

use Exception;
use Carbon\Carbon;
use App\Models\UlbMaster;
use Illuminate\Http\Request;
use App\Models\WorkflowTrack;
use App\Repository\Trade\Trade;
use App\MicroServices\DocUpload;
use App\Models\Trade\TradeOwner;
use App\Repository\Trade\ITrade;
use App\Models\Trade\TradeLicence;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Workflows\WfWorkflow;
use Illuminate\Foundation\Auth\User;
use App\Http\Requests\Trade\ReqInbox;
use Illuminate\Support\Facades\Config;
use App\EloquentModels\Common\ModelWard;
use App\Models\Trade\ActiveTradeLicence;
use App\Models\Trade\TradeParamFirmType;
use App\Models\Trade\TradeParamItemType;
use App\Repository\Common\CommonFunction;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Trade\ReqAddRecorde;
use App\Models\Workflows\WfActiveDocument;
use App\Http\Requests\Trade\paymentCounter;
use App\Http\Requests\Trade\ReqApplyDenail;
use App\Http\Requests\Trade\ReqPaybleAmount;
use App\Models\Trade\TradeParamCategoryType;
use App\Http\Requests\Trade\ReqPostNextLevel;
use App\Models\Trade\TradeParamOwnershipType;
use App\Http\Requests\Trade\ReqUpdateBasicDtl;
use App\Traits\Trade\TradeTrait;

class TradeApplication extends Controller
{
    use TradeTrait;

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
        $this->Repository = $TradeRepository;
        $this->_modelWard = new ModelWard();
        $this->_parent = new CommonFunction();
    }
    public function getMstrForNewLicense(Request $request)
    {
        try{
            $request->request->add(["applicationType"=>"NEWLICENSE"]);
            return $this->getApplyData($request);
        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function getMstrForRenewal(Request $request)
    {
        try{
            $request->request->add(["applicationType"=>"RENEWAL"]);
            return $this->getApplyData($request);
        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function getMstrForAmendment(Request $request)
    {
        try{
            $request->request->add(["applicationType"=>"AMENDMENT"]);
            return $this->getApplyData($request);
        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function getMstrForSurender(Request $request)
    {
        try{
            $request->request->add(["applicationType"=>"SURRENDER"]);
            return $this->getApplyData($request);
        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function getApplyData(Request $request)
    {
        try {
            $refUser            = Auth()->user();
            $refUserId          = $refUser->id;
            $refUlbId           = $refUser->ulb_id ?? $request->ulbId;
            $refWorkflowId      = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
            $mUserType          = $this->_parent->userType($refWorkflowId);
            $mApplicationTypeId = Config::get("TradeConstant.APPLICATION-TYPE." . $request->applicationType);
            $mnaturOfBusiness   = null;
            $data               = array();
            $rules["applicationType"] = "required|string|in:NEWLICENSE,RENEWAL,AMENDMENT,SURRENDER";
            if (!in_array($mApplicationTypeId, [1])) 
            {
                $rules["licenseId"] = "required|digits_between:1,9223372036854775807";
            }
            
            $validator = Validator::make($request->all(), $rules,);
            if ($validator->fails()) 
            {
                return responseMsg(false, $validator->errors(), $request->all());
            }
            #------------------------End Declaration-----------------------

            $data['userType']           = $mUserType;
            $data["firmTypeList"]       = TradeParamFirmType::List();
            $data["ownershipTypeList"]  = TradeParamOwnershipType::List();
            $data["categoryTypeList"]   = TradeParamCategoryType::List();
            $data["natureOfBusiness"]   = TradeParamItemType::List(true);
            if (isset($request->licenseId) && $request->licenseId  && $mApplicationTypeId != 1) 
            {
                $mOldLicenceId = $request->licenseId;
                $nextMonth = Carbon::now()->addMonths(1)->format('Y-m-d');
                $refOldLicece = $this->Repository->getLicenceById($mOldLicenceId); //TradeLicence::find($mOldLicenceId)
                if (!$refOldLicece) {
                    throw new Exception("Old Licence Not Found");
                }
                if (!$refOldLicece->is_active) {
                    $newLicense = ActiveTradeLicence::where("license_no", $refOldLicece->license_no)
                        ->orderBy("id")
                        ->first();
                    throw new Exception("Application Already Apply Please Track  " . $newLicense->application_no);
                }
                if ($refOldLicece->valid_upto > $nextMonth) {
                    throw new Exception("Licence Valice Upto " . $refOldLicece->valid_upto);
                }
                if ($refOldLicece->pending_status != 5) {
                    throw new Exception("Application not approved Please Track  " . $refOldLicece->application_no);
                }
                $refOldOwneres = TradeOwner::owneresByLId($request->licenseId);
                $mnaturOfBusiness = TradeParamItemType::itemsById($refOldLicece->nature_of_bussiness);
                $natur = array();
                foreach ($mnaturOfBusiness as $val) {
                    $natur[] = [
                        "id" => $val->id,
                        "trade_item" => "(" . $val->trade_code . ") " . $val->trade_item
                    ];
                }
                $refOldLicece->nature_of_bussiness = $natur;
                $data["licenceDtl"]     =  $refOldLicece;
                $data["ownerDtl"]       = $refOldOwneres;
                $refUlbId = $refOldLicece->ulb_id;
            }
            
            if (in_array(strtoupper($mUserType), ["ONLINE", "JSK", "SUPER ADMIN", "TL"])) 
            {
                $data['wardList'] = $this->_modelWard->getAllWard($refUlbId)->map(function ($val) {
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $data['wardList'] = objToArray($data['wardList']);
            } 
            else 
            {
                $data['wardList'] = $this->_parent->WardPermission($refUserId);
            }
            return responseMsg(true, "", remove_null($data));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    # Serial No : 01
    public function applyApplication(ReqAddRecorde $request)
    {
        $refUser            = Auth()->user();
        $refUserId          = $refUser->id;
        $refUlbId           = $refUser->ulb_id;
        if ($refUser->user_type == Config::get("TradeConstant.CITIZEN")) 
        {
            $refUlbId = $request->ulbId ?? 0;
        }
        $refWorkflowId      = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
        $mUserType          = $this->_parent->userType($refWorkflowId);
        $refWorkflows       = $this->_parent->iniatorFinisher($refUserId, $refUlbId, $refWorkflowId);
        $mApplicationTypeId = Config::get("TradeConstant.APPLICATION-TYPE." . $request->applicationType);
        try {
            if (!in_array(strtoupper($mUserType), ["ONLINE", "JSK", "UTC", "TC", "SUPER ADMIN", "TL"])) {
                throw new Exception("You Are Not Authorized For This Action !");
            }
            if (!$mApplicationTypeId) {
                throw new Exception("Invalide Application Type");
            }
            if (!$refWorkflows) {
                throw new Exception("Workflow Not Available");
            }
            if (!$refWorkflows['initiator']) {
                throw new Exception("Initiator Not Available");
            }
            if (!$refWorkflows['finisher']) {
                throw new Exception("Finisher Not Available");
            }
            // return $request->applicationType;
            if (in_array($mApplicationTypeId, ["2", "3", "4"]) && (!$request->licenseId || !is_numeric($request->licenseId))) {
                throw new Exception("Old licence Id Requird");
            }            
            return $this->Repository->addRecord($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
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

    public function getDocList(Request $request)
    {
        $tradC = new Trade();
        return $tradC->getLicenseDocLists($request);
    }


    # Serial No : 04
    public function paymentReceipt(Request $request)
    {
        $id = $request->id;
        $transectionId =  $request->transectionId;
        $request->setMethod('POST');
        $request->request->add(["id"=>$id,"transectionId"=>$transectionId]);       
        $rules =[
            "id" => "required|digits_between:1,9223372036854775807",
            "transectionId" => "required|digits_between:1,9223372036854775807",
        ];
        $validator = Validator::make($request->all(), $rules,);
        if ($validator->fails()) 
        {
            return responseMsg(false, $validator->errors(), $request->all());
        }
        return $this->Repository->readPaymentReceipt($id, $transectionId);
    }
    # Serial No : 05
    public function documentUpload(Request $request)
    {
        return $this->Repository->documentUpload($request);
    }
    
    # Serial No : 07
    public function documentVirify(Request $request)
    {
        return $this->Repository->documentVirify($request);
    }
    # Serial No : 08 
    public function getLicenceDtl(Request $request)
    {

        $rules["applicationId"] = "required|digits_between:1,9223372036854775807";
        $validator = Validator::make($request->all(), $rules,);
        if ($validator->fails()) {
            return responseMsg(false, $validator->errors(), $request->all());
        }
        return $this->Repository->readLicenceDtl($request);
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
        $rules = [
            "entityValue"   =>  "required",
            "entityName"    =>  "required",
        ];
        $validator = Validator::make($request->all(), $rules,);
        if ($validator->fails()) {
            return responseMsg(false, $validator->errors(), $request->all());
        }
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


    # Serial No
    public function backToCitizen(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|integer',
            'workflowId' => 'required|integer',
            'currentRoleId' => 'required|integer',
            'comment' => 'required|string'
        ]);

        try {
            $activeLicence = ActiveTradeLicence::find($req->applicationId);
            $track = new WorkflowTrack();
            DB::beginTransaction();
            $initiatorRoleId = $activeLicence->initiator_role;
            $activeLicence->current_role = $initiatorRoleId;
            $activeLicence->is_parked = true;
            $activeLicence->save();

            $metaReqs['moduleId'] = Config::get('module-constants.TRADE_MODULE_ID');
            $metaReqs['workflowId'] = $activeLicence->workflow_id;
            $metaReqs['refTableDotId'] = 'active_trade_licences.id';
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $req->request->add($metaReqs);
            $track->saveTrack($req);

            DB::commit();
            return responseMsgs(true, "Successfully Done", "", "010111", "1.0", "350ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    # Serial No : 18
    public function postNextLevel(Request $request)
    {

        $request->validate([
            'applicationId' => 'required|integer',
            'senderRoleId' => 'required|integer',
            'receiverRoleId' => 'required|integer',
            'comment' => 'required',
        ]);

        try {
            // Trade Application Update Current Role Updation
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $refWorkflowId = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
            $workflowId = WfWorkflow::where('id', $refWorkflowId)
                ->where('ulb_id', $ulb_id)
                ->first();
            if (!$workflowId) 
            {
                throw new Exception("Workflow Not Available");
            }
            
            $licence = ActiveTradeLicence::find($request->applicationId);
            if(!$licence)
            {
                throw new Exception("Data Not Found");
            }
            $allRolse = collect($this->_parent->getAllRoles($user_id,$ulb_id,$refWorkflowId,0,true));
            $receiverRole = array_values(objToArray($allRolse->where("id",$request->receiverRoleId)))[0]??[];
            $role = $this->_parent->getUserRoll($user_id,$ulb_id,$refWorkflowId);
            if($licence->payment_status!=1 && ($role->serial_no  < $receiverRole["serial_no"]??0))
            {
                throw new Exception("Payment Not Clear");
            }
            
            if($licence->current_role != $role->role_id)
            {
                throw new Exception("You Have Not Pending This Application");
            }
            $sms ="Application BackWord To ".$receiverRole["role_name"]??"";
            
            if($role->serial_no  < $receiverRole["serial_no"]??0)
            {
                $sms ="Application Forward To ".$receiverRole["role_name"]??"";
            }
            $tradC = new Trade();
            $documents = $tradC->checkWorckFlowForwardBackord($request);
            if(($licence->max_level_attained < $receiverRole["serial_no"]??0) && !$documents)
            {
                throw new Exception("Not Every Actoin Are Performed");
            }
            
            if($role->can_upload_document)
            {
                if(($role->serial_no < $receiverRole["serial_no"]??0))
                {
                    $licence->document_upload_status = true;
                    $licence->pending_status = 1;
                    $licence->is_parked = false;
                }
                if(($role->serial_no > $receiverRole["serial_no"]??0))
                {
                    $licence->document_upload_status = false;
                }
            }
            if($role->can_verify_document)
            {
                if(($role->serial_no < $receiverRole["serial_no"]??0))
                {
                    $licence->is_doc_verified = true;
                    $licence->doc_verified_by = $user_id;
                    $licence->doc_verify_date = Carbon::now()->format("Y-m-d");
                }
                if(($role->serial_no > $receiverRole["serial_no"]??0))
                {
                    $licence->is_doc_verified = false;
                }
                
            }

            DB::beginTransaction();
            $licence->max_level_attained = ($licence->max_level_attained < ($receiverRole["serial_no"]??0)) ? ($receiverRole["serial_no"]??0) : $licence->max_level_attained;
            $licence->current_role = $request->receiverRoleId;
            $licence->update();


            $metaReqs['moduleId'] = Config::get('module-constants.TRADE_MODULE_ID');
            $metaReqs['workflowId'] = $licence->workflow_id;
            $metaReqs['refTableDotId'] = 'active_trade_licences';
            $metaReqs['refTableIdValue'] = $request->applicationId;
            $request->request->add($metaReqs);

            $track = new WorkflowTrack();
            $track->saveTrack($request);

            DB::commit();
            return responseMsgs(true, $sms, "", "010109", "1.0", "286ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    # Serial No
    public function approveReject(Request $req)
    {
        try {
            $req->validate([
                "applicationId" => "required",
                "status" => "required"
            ]);
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $refWorkflowId = Config::get('workflow-constants.TRADE_WORKFLOW_ID');

            $activeLicence = ActiveTradeLicence::find($req->applicationId);
            $role = $this->_parent->getUserRoll($user_id,$ulb_id,$refWorkflowId);
            if ($activeLicence->finisher_role != $role->role_id) {
                return responseMsg(false, "Forbidden Access", "");
            }
            DB::beginTransaction();

            // Approval
            if ($req->status == 1) 
            {
                $refUlbDtl          = UlbMaster::find($activeLicence->ulb_id);
                // Objection Application replication
                $approvedLicence = $activeLicence->replicate();
                $approvedLicence->setTable('trade_licences');
                $approvedLicence->id = $activeLicence->id;
                $status = $this->giveValidity($approvedLicence);
                if(!$status)
                {
                    throw new Exception("Some Error Occurs");
                }
                $approvedLicence->save();
                $activeLicence->delete();
                $licenseNo = $approvedLicence->license_no;
                $msg =  "Application Successfully Approved !!. Your License No Is ".$licenseNo;
                $sms = trade(["application_no"=>$approvedLicence->application_no,"licence_no"=>$approvedLicence->license_no,"ulb_name"=>$refUlbDtl->ulb_name??""],"Application Approved");
            }

            // Rejection
            if ($req->status == 0) 
            {
                // Objection Application replication
                $approvedLicence = $activeLicence->replicate();
                $approvedLicence->setTable('rejected_trade_licences');
                $approvedLicence->id = $activeLicence->id;
                $approvedLicence->save();
                $activeLicence->delete();
                $msg = "Application Successfully Rejected !!";
                // $sms = trade(["application_no"=>$approvedLicence->application_no,"licence_no"=>$approvedLicence->license_no,"ulb_name"=>$refUlbDtl->ulb_name??""],"Application Approved");
            }
            if(($sms["status"]??false))
            {
                $owners = $this->getAllOwnereDtlByLId($req->applicationId);
                foreach($owners as $val)
                {
                    $respons=send_sms($val["mobile_no"],$sms["sms"],$sms["temp_id"]);
                }

            }
            DB::commit();

            return responseMsgs(true, $msg, "", '010811', '01', '474ms-573', 'Post', '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    # Serial No : 19
    public function provisionalCertificate(Request $request)
    {
        $id=$request->id;
        $request->setMethod('POST');
        $request->request->add(["id"=>$id]);       
        $rules =[
            "id" => "required|digits_between:1,9223372036854775807",
        ];
        $validator = Validator::make($request->all(), $rules,);
        if ($validator->fails()) 
        {
            return responseMsg(false, $validator->errors(), $request->all());
        }
        return $this->Repository->provisionalCertificate($request->id);
    }
    # Serial No : 20
    public function licenceCertificate(Request $request)
    { 
        $id=$request->id;
        $request->setMethod('POST');
        $request->request->add(["id"=>$id]);       
        $rules =[
            "id" => "required|digits_between:1,9223372036854775807",
        ];
        $validator = Validator::make($request->all(), $rules,);
        if ($validator->fails()) 
        {
            return responseMsg(false, $validator->errors(), $request->all());
        }
        return $this->Repository->licenceCertificate($request->id);
    }
    # Serial No : 21
    public function applyDenail(ReqApplyDenail $request)
    {
        try {
            $user = Auth()->user();
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $refWorkflowId = Config::get('workflow-constants.TRADE_NOTICE_ID');
            $role = $this->_parent->getUserRoll($userId, $ulbId, $refWorkflowId);
            if (!$role) {
                throw new Exception("You Are Not Authorized");
            }
            $userType = $this->_parent->userType($refWorkflowId);
            if (!in_array(strtoupper($userType), ["TC", "UTC"])) {
                throw new Exception("You Are Not Authorize For Apply Denial");
            }
            if ($request->getMethod() == 'GET') {
                $data['wardList'] = $this->_parent->WardPermission($userId);
                return  responseMsg(true, "", $data);
            }
            return $this->Repository->addDenail($request);
        } catch (Exception $e) {
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
        try {
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $workflow_id = Config::get('workflow-constants.TRADE_NOTICE_ID');
            $role = $this->_parent->getUserRoll($user_id, $ulb_id, $workflow_id);
            $role_id = $role->role_id ?? -1;
            if (!$role  || !in_array($role_id, [10])) {
                throw new Exception("You Are Not Authorized");
            }
            return $this->Repository->denialInbox($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    # Serial No : 25
    public function denialview(Request $request)
    {
        $id = $request->id;
        $mailID = $request->mailID;
        return $this->Repository->denialView($id, $mailID, $request);
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

    /**
     *  get uploaded documents
     */
    public function getUploadDocuments(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|digits_between:1,9223372036854775807'
        ]);
        try {
            $mWfActiveDocument = new WfActiveDocument();
            $mActiveTradeLicence = new ActiveTradeLicence();
            $modul_id = Config::get('module-constants.TRADE_MODULE_ID');
            $licenceDetails = $mActiveTradeLicence->getLicenceNo($req->applicationId);
            if (!$licenceDetails)
                throw new Exception("Application Not Found for this application Id");

            $appNo = $licenceDetails->application_no;
            $tradR = new Trade();
            $documents = $mWfActiveDocument->getTradeDocByAppNo($licenceDetails->id,$licenceDetails->workflow_id,$modul_id);
            
            // $documents = $documents->map(function($val) use($tradR){
            //     $path =  $tradR->readDocumentPath($val->doc_path);
            //     $val->doc_path = !empty(trim($val->doc_path)) ? $path : null;
            //     return $val;
            // });
            return responseMsgs(true, "Uploaded Documents", remove_null($documents), "010102", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010202", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * 
     */
    public function uploadDocument(Request $req)
    {
        $req->validate([
            "applicationId" => "required|digits_between:1,9223372036854775807",
            "document" => "required|mimes:pdf,jpeg,png,jpg,gif",
            "docName" => "required",
            "docCode" => "required",
            "ownerId" => "nullable|digits_between:1,9223372036854775807"
        ]);

        try {
            $tradC = new Trade();
            $documents = $tradC->getLicenseDocLists($req);
            if(!$documents->original["status"])
            {
                throw new Exception($documents->original["message"]);
            };
            $applicationDoc = $documents->original["data"]["listDocs"];
            $applicationDocName = $applicationDoc->implode("docName",",");
            $applicationDocCode = $applicationDoc->where("docName",$req->docName)->first();
            $applicationCode = $applicationDocCode?$applicationDocCode["masters"]->implode("documentCode",","):"";
            // $mandetoryDoc = $applicationDoc->whereIn("docType",["R","OR"]);

            $ownerDoc = $documents->original["data"]["ownerDocs"];
            $ownerDocsName = $ownerDoc->map(function($val){
                $doc = $val["documents"]->map(function($val1){
                    return["docType"=>$val1["docType"],"docName"=>$val1["docName"],"documentCode"=>$val1["masters"]->implode("documentCode",",")];
                });
                $ownereId = $val["ownerDetails"]["ownerId"];
                $docNames = $val["documents"]->implode("docName",",");
                return ["ownereId"=>$ownereId,"docNames"=>$docNames,"doc"=>$doc];
            });
            $ownerDocNames = $ownerDocsName->implode("docNames",",");
            
            $ownerIds = $ownerDocsName->implode("ownereId",",");
            $particuler = (collect($ownerDocsName)->where("ownereId",$req->ownerId)->values())->first();
           
            $ownereDocCode = $particuler?collect($particuler["doc"])->where("docName",$req->docName)->all():"";
            
            $particulerDocCode = collect($ownereDocCode)->implode("documentCode",",");
            if(!(in_array($req->docName,explode(",",$applicationDocName))==true || in_array($req->docName,explode(",",$ownerDocNames)) ==true))
            {
                throw new Exception("Invalid Doc Name Pass");
            }
            if(in_array($req->docName,explode(",",$applicationDocName)) && (empty($applicationDocCode) || !(in_array($req->docCode,explode(",",$applicationCode)))))
            {
                throw new Exception("Invalid Application Doc Code Pass");
            }
            if(in_array($req->docName,explode(",",$ownerDocNames)) && (!(in_array($req->ownerId,explode(",",$ownerIds)))))
            {
                throw new Exception("Invalid ownerId Pass");
            }
            if(in_array($req->docName,explode(",",$ownerDocNames)) && ($ownereDocCode && !(in_array($req->docCode,explode(",",$particulerDocCode)))))
            {
                throw new Exception("Invalid Ownere Doc Code Pass");
            }
            
            $metaReqs = array();
            $docUpload = new DocUpload;
            $mWfActiveDocument = new WfActiveDocument();
            $mActiveTradeLicence = new ActiveTradeLicence();
            $relativePath = Config::get('TradeConstant.TRADE_RELATIVE_PATH');
            $getLicenceDtls = $mActiveTradeLicence->getLicenceNo($req->applicationId);
            $refImageName = $req->docCode;
            $refImageName = $getLicenceDtls->id . '-' . str_replace(' ', '_', $refImageName);
            $document = $req->document;

            $imageName = $docUpload->upload($refImageName, $document, $relativePath);

            $metaReqs['moduleId'] = Config::get('module-constants.TRADE_MODULE_ID');
            $metaReqs['activeId'] = $getLicenceDtls->id;
            $metaReqs['workflowId'] = $getLicenceDtls->workflow_id;
            $metaReqs['ulbId'] = $getLicenceDtls->ulb_id;
            $metaReqs['relativePath'] = $relativePath;
            $metaReqs['document'] = $imageName;
            $metaReqs['docCode'] = $req->docName;//$req->docCode;
            
            if(in_array($req->docName,explode(",",$ownerDocNames)) ) 
            {
                $metaReqs['ownerDtlId'] = $req->ownerId;
            }
            
            #reupload documents;
            if($privDoc = $mWfActiveDocument->getTradeAppByAppNoDocId($getLicenceDtls->id,$getLicenceDtls->ulb_id, collect($req->docName), $metaReqs['ownerDtlId']??null))
            {
                if($privDoc->verify_status!=2)
                {
                    // dd("update");
                    $arr["verify_status"] = 0;
                    $arr['relative_path'] = $relativePath;
                    $arr['document'] = $imageName;
                    $arr['doc_code'] = $req->docName;
                    $arr['owner_dtl_id'] = $metaReqs['ownerDtlId']??null;
                    $mWfActiveDocument->docVerifyReject($privDoc->id,$arr);
                }
                else
                {
                    // dd("reupload");
                    $mWfActiveDocument->docVerifyReject($privDoc->id,["status"=>0]);
                    $metaReqs = new Request($metaReqs);
                    $mWfActiveDocument->postDocuments($metaReqs);
                }
                return responseMsgs(true, $req->docName." Update Successful", "", "010201", "1.0", "", "POST", $req->deviceId ?? "");
            }
            #new documents;
            
            $metaReqs = new Request($metaReqs);
            $mWfActiveDocument->postDocuments($metaReqs);
            return responseMsgs(true,  $req->docName." Uploadation Successful", "", "010201", "1.0", "", "POST", $req->deviceId ?? "");
        } 
        catch (Exception $e) 
        {
            // dd($e->getMessage(),$e->getFile(),$e->getLine());
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
}
