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
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Workflows\WfWorkflow;
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
use App\Http\Requests\Trade\ReqGetUpdateBasicDtl;
use App\Http\Requests\Trade\ReqPaybleAmount;
use App\Models\Trade\TradeParamCategoryType;
use App\Models\Trade\TradeParamOwnershipType;
use App\Http\Requests\Trade\ReqUpdateBasicDtl;
use App\Models\Trade\ActiveTradeOwner;
use App\Traits\Trade\TradeTrait;

class TradeApplication extends Controller
{
    use TradeTrait;

    #====================[🅾️OWNER DETAILS🅾️]==========================
        /**
         * | Created On-01-10-2022
         * | Created By-Sandeep Bara
         * --------------------------------------------------------------------------------------
         * | Controller regarding with Trade Module
         STATUS [OPEN]
        */

    #======================[❎VARIABLES❎]============================
        /**
         * @var  obj -> $_DB  | trade connection instanse 
         */
        protected $_DB;        
        protected $_DB_READ;
        protected $_DB_MASTER; 

        /**
         * @var string -> $_DB_NAME | trade connection name
         */
        protected $_DB_NAME; 
        
        /**
         * @var obj -> $_NOTICE_DB | notice connection instanse
         */
        protected $_NOTICE_DB;

        /**
         * @var string -> $_NOTICE_DB_NAME | notice connection name
         */
        protected $_NOTICE_DB_NAME;

        /**
         * @var class -> $_MODEL_WARD
         */
        protected $_MODEL_WARD;

        /**
         * @var class -> $_COMMON_FUNCTION
         */
        protected $_COMMON_FUNCTION;
        protected $_REPOSITORY;
        protected $_WF_MASTER_Id;
        protected $_WF_NOTICE_MASTER_Id;
        protected $_MODULE_ID;
        protected $_REF_TABLE;
        protected $_TRADE_CONSTAINT;
    #======================[❎REPOSITRY AND CONTROLLER VARIABLE❎]============================
        protected $_CONTROLLER_TRADE;
    #======================[❎MODEL VARIABLE❎]============================
        protected $_MODEL_TradeParamFirmType;
        protected $_MODEL_TradeParamOwnershipType;
        protected $_MODEL_TradeParamCategoryType;
        protected $_MODEL_TradeParamItemType;
        protected $_MODEL_ActiveTradeLicence;
        protected $_MODEL_ActiveTradeOwner;

    public function __construct(ITrade $TradeRepository)
    {        
        $this->_DB_NAME = "pgsql_trade";
        $this->_NOTICE_DB = "pgsql_notice";
        $this->_DB = DB::connection( $this->_DB_NAME );
        $this->_DB_MASTER = DB::connection("pgsql_master");
        $this->_DB_READ = DB::connection( $this->_DB_NAME."::read" );
        $this->_NOTICE_DB = DB::connection($this->_NOTICE_DB);
        // DB::enableQueryLog();
        // $this->_DB->enableQueryLog();
        // $this->_NOTICE_DB->enableQueryLog();

        $this->_REPOSITORY = $TradeRepository;
        $this->_MODEL_WARD = new ModelWard();
        $this->_COMMON_FUNCTION = new CommonFunction();
        $this->_CONTROLLER_TRADE = new Trade();

        $this->_MODEL_TradeParamFirmType = new TradeParamFirmType($this->_DB_NAME );
        $this->_MODEL_TradeParamOwnershipType = new TradeParamOwnershipType($this->_DB_NAME );
        $this->_MODEL_TradeParamCategoryType = new TradeParamCategoryType($this->_DB_NAME );
        $this->_MODEL_TradeParamItemType = new TradeParamItemType($this->_DB_NAME );
        $this->_MODEL_ActiveTradeLicence = new ActiveTradeLicence( $this->_DB_NAME );
        $this->_MODEL_ActiveTradeOwner  = new ActiveTradeOwner($this->_DB_NAME);

        $this->_WF_MASTER_Id = Config::get('workflow-constants.TRADE_MASTER_ID');
        $this->_WF_NOTICE_MASTER_Id = Config::get('workflow-constants.TRADE_NOTICE_ID');
        $this->_MODULE_ID = Config::get('module-constants.TRADE_MODULE_ID');
        $this->_TRADE_CONSTAINT = Config::get("TradeConstant");
        $this->_REF_TABLE = $this->_TRADE_CONSTAINT["TRADE_REF_TABLE"];
    }
    

    #=======================[❤️TRANSACTION BEGIN❤️]==============================
        /** 
         * @var $db1 default database name
         * @var $db2 trade database name
         * @var $db3 notice database name
         * @return void
         */
    public function begin()
    {
        $db1 = DB::connection()->getDatabaseName();
        $db2 = $this->_DB->getDatabaseName();
        $db3 = $this->_NOTICE_DB->getDatabaseName();
        $db4 = $this->_DB_MASTER->getDatabaseName();
        DB::beginTransaction();
        if($db1!=$db2 )
            $this->_DB->beginTransaction();
        if($db1!=$db3 && $db2!=$db3)
            $this->_NOTICE_DB->beginTransaction();
        if($db1!=$db4 && $db2!=$db4 && $db3!=$db4) 
            $this->_DB_MASTER->beginTransaction();
    }

    #=======================[❤️TRANSACTION ROLLBACK❤️]==============================
        /** 
         * @var $db1 default database name
         * @var $db2 trade database name
         * @var $db3 notice database name
         * @return void
         */
    public function rollback()
    {
        $db1 = DB::connection()->getDatabaseName();
        $db2 = $this->_DB->getDatabaseName();
        $db3 = $this->_NOTICE_DB->getDatabaseName();
        $db4 = $this->_DB_MASTER->getDatabaseName();
        DB::rollBack();
        if($db1!=$db2 )
            $this->_DB->rollBack();
        if($db1!=$db3 && $db2!=$db3)
            $this->_NOTICE_DB->rollBack();
        if($db1!=$db4 && $db2!=$db4 && $db3!=$db4) 
            $this->_DB_MASTER->rollBack();
    }
     
    #=======================[❤️TRANSACTION COMMIT❤️]==============================
        /** 
         * @var $db1 default database name
         * @var $db2 trade database name
         * @var $db3 notice database name
         * @return void
         */
    public function commit()
    {
        $db1 = DB::connection()->getDatabaseName();
        $db2 = $this->_DB->getDatabaseName();
        $db3 = $this->_NOTICE_DB->getDatabaseName();
        $db4 = $this->_DB_MASTER->getDatabaseName();
        DB::commit();
        if($db1!=$db2 )        
            $this->_DB->commit();
        if($db1!=$db3 && $db2!=$db3)
            $this->_NOTICE_DB->commit();
        if($db1!=$db4 && $db2!=$db4 && $db3!=$db4) 
            $this->_DB_MASTER->commit();
    }
    
    #=======================[📖 MDM DATA FOR APPLICATION APPLY | S.L (1.0) 📖]===============================================        
    public function getApplyData(Request $request)
    {
        try {
            $refUser            = Auth()->user();
            $refUserId          = $refUser->id;
            $refUlbId           = $refUser->ulb_id ?? $request->ulbId;
            $refWorkflowId      = $this->_WF_MASTER_Id;
            $mUserType          = $this->_COMMON_FUNCTION->userType($refWorkflowId);
            $mApplicationTypeId = $this->_TRADE_CONSTAINT["APPLICATION-TYPE"][$request->applicationType] ?? null;
            $mnaturOfBusiness   = null;
            $data               = array();
            $rules["applicationType"] = "required|string|in:".collect(flipConstants($this->_TRADE_CONSTAINT["APPLICATION-TYPE"]))->implode(",");
            if (!in_array($mApplicationTypeId, [1])) {
                $rules["licenseId"] = "required|digits_between:1,9223372036854775807";
            }
            $validator = Validator::make($request->all(), $rules,);
            if ($validator->fails()) {
                return responseMsg(false, $validator->errors(), "");
            }
            #------------------------End Declaration-----------------------  
            
            if (isset($request->licenseId) && $request->licenseId  && $mApplicationTypeId != 1) {
                $mOldLicenceId = $request->licenseId;
                $nextMonth = Carbon::now()->addMonths(1)->format('Y-m-d');
                $refOldLicece = $this->_REPOSITORY->getLicenceById($mOldLicenceId); //TradeLicence::find($mOldLicenceId)
                if (!$refOldLicece) {
                    throw new Exception("Old Licence Not Found");
                }
                if (!$refOldLicece->is_active) {
                    $newLicense = $this->_MODEL_ActiveTradeLicence->readConnection()->where("license_no", $refOldLicece->license_no)
                        ->orderBy("id")
                        ->first();
                    throw new Exception("Application Already Apply Please Track  " . $newLicense->application_no);
                }
                if ($refOldLicece->valid_upto > $nextMonth && !in_array($mApplicationTypeId, [3, 4])) {
                    throw new Exception("Licence Valice Upto " . $refOldLicece->valid_upto);
                }
                if ($refOldLicece->valid_upto < (Carbon::now()->format('Y-m-d')) && in_array($mApplicationTypeId, [3, 4])) {
                    throw new Exception("Licence Was Expired Please Renewal First");
                }
                if ($refOldLicece->pending_status != 5) {
                    throw new Exception("Application not approved Please Track  " . $refOldLicece->application_no);
                }
                $refOldOwneres = $refOldLicece->owneres()->get();
                $mnaturOfBusiness = $this->_MODEL_TradeParamItemType->readConnection()->itemsById($refOldLicece->nature_of_bussiness);
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
            if (in_array(strtoupper($mUserType), $this->_TRADE_CONSTAINT["CANE-NO-HAVE-WARD"])) {               
                $data['wardList'] = $this->_MODEL_WARD->getOldWard($refUlbId)->map(function ($val) {
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $data['wardList'] = objToArray($data['wardList']);
            } else {
                $data['wardList'] = $this->_COMMON_FUNCTION->oldWardPermission($refUserId);
            }
            $data['userType']           = $mUserType;
            $data["firmTypeList"]       =$this->_MODEL_TradeParamFirmType->List();
            $data["ownershipTypeList"]  =$this->_MODEL_TradeParamOwnershipType->List();
            $data["categoryTypeList"]   =$this->_MODEL_TradeParamCategoryType->List();
            $data["natureOfBusiness"]   =$this->_MODEL_TradeParamItemType->List(true);
            return responseMsg(true, "", remove_null($data));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    #=====================[📝 📖 CREATE NEW APPLICATION | S.L (2.0) 📖 📝]========================================================
    public function applyApplication(ReqAddRecorde $request)
    { 
        $refUser            = Auth()->user();
        $refUserId          = $refUser->id;
        $refUlbId           = $refUser->ulb_id;
        if ($refUser->user_type == $this->_TRADE_CONSTAINT["CITIZEN"]) {
            $refUlbId = $request->ulbId ?? 0;
        }
        $refWorkflowId      = $this->_WF_MASTER_Id;
        $mUserType          = $this->_COMMON_FUNCTION->userType($refWorkflowId);
        $refWorkflows       = $this->_COMMON_FUNCTION->iniatorFinisher($refUserId, $refUlbId, $refWorkflowId);
        $mApplicationTypeId = ($this->_TRADE_CONSTAINT["APPLICATION-TYPE"][$request->applicationType] ?? null);
        try {
            if ((!$this->_COMMON_FUNCTION->checkUsersWithtocken("users"))) {
                throw new Exception("Citizen Not Allowed");
            }
            if (!in_array(strtoupper($mUserType), $this->_TRADE_CONSTAINT["CANE-APPLY-APPLICATION"])) {
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
            return $this->_REPOSITORY->addRecord($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    #====================[📝📖 CREATE NEW PAYMENT | S.L (2.1) 📖📝]========================================================
    public function paymentCounter(paymentCounter $request)
    {
        try {
            if (!$this->_COMMON_FUNCTION->checkUsersWithtocken("users")) {
                throw new Exception("Citizen Not Allowed");
            }
            return $this->_REPOSITORY->paymentCounter($request);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    #====================[📖 APPLICATION DTL | S.L (2.1.1) 📖]========================================================
    public function paybleAmount(ReqPaybleAmount $request)
    {
        return $this->_REPOSITORY->getPaybleAmount($request);
    }
    #====================[📖 UPDATE NEW APPLICATION | S.L (3.0) 📖]========================================================
    public function updateLicenseBo(ReqGetUpdateBasicDtl $request)
    {
        return $this->_REPOSITORY->updateLicenseBo($request);
    }

    #====================[📝📖 UPDATE NEW APPLICATION | S.L (3.1) 📖📝]========================================================
    public function updateBasicDtl(ReqUpdateBasicDtl $request)
    {
        return $this->_REPOSITORY->updateBasicDtl($request);
    }

    #====================[📖 APPLICATION REQUIED DOC LIST | S.L (4.0) 📖]========================================================
    public function getDocList(Request $request)
    {
        $tradC = $this->_CONTROLLER_TRADE;
        $response =  $tradC->getLicenseDocLists($request);
        if ($response->original["status"]) {
            $ownerDoc = $response->original["data"]["ownerDocs"];
            $Wdocuments = collect();
            $ownerDoc->map(function ($val) use ($Wdocuments) {
                $ownerId = $val["ownerDetails"]["ownerId"] ?? "";
                $val["documents"]->map(function ($val1) use ($Wdocuments, $ownerId) {
                    $val1["ownerId"] = $ownerId;
                    $val1["is_uploded"] = (in_array($val1["docType"], ["R", "OR"]))  ? ((!empty($val1["uploadedDoc"])) ? true : false) : true;
                    $val1["is_docVerify"] = !empty($val1["uploadedDoc"]) ?  (((collect($val1["uploadedDoc"])->all())["verifyStatus"] != 0) ? true : false) : true;

                    collect($val1["masters"])->map(function ($v) use ($Wdocuments) {
                        $Wdocuments->push($v);
                    });
                });
            });
            if ($Wdocuments->isEmpty()) {
                $newrespons = [];
                foreach ($response->original["data"] as $key => $val) {
                    if ($key != "ownerDocs") {
                        $newrespons[$key] = $val;
                    }
                }

                $response->original["data"] = $newrespons;
                $response = responseMsgs(
                    $response->original["status"],
                    $response->original["message"],
                    $response->original["data"],
                    $response->original["meta-data"]["apiId"] ?? "",
                    $response->original["meta-data"]["version"] ?? "",
                    $response->original["meta-data"]["responsetime"] ?? "",
                    $response->original["meta-data"]["action"] ?? "",
                    $response->original["meta-data"]["deviceId"] ?? ""
                );
            }
        }
        return $response;
    }

    #====================[📖 APPLICATION UPLOADED DOC LIST | S.L (4.1) 📖]========================================================
    public function getUploadDocuments(Request $req)
    {  
        $req->validate([
            'applicationId' => 'required|digits_between:1,9223372036854775807'
        ]);
        try {
            $mWfActiveDocument = new WfActiveDocument();
            $mActiveTradeLicence = $this->_MODEL_ActiveTradeLicence;
            $modul_id = $this->_MODULE_ID;
            $licenceDetails = $mActiveTradeLicence->getLicenceNo($req->applicationId);
            if (!$licenceDetails)
                throw new Exception("Application Not Found for this application Id");

            $appNo = $licenceDetails->application_no;
            $tradR = $this->_CONTROLLER_TRADE;
            $documents = $mWfActiveDocument->getTradeDocByAppNo($licenceDetails->id, $licenceDetails->workflow_id, $modul_id)->map(function($val){
                $docUpload = new DocUpload();
                // $val->reference_no = "REF16946096910491";
                $api = $docUpload->getSingleDocUrl($val);
                $val->doc_path = $api["doc_path"]??"";
                $val->api =$api??"";
                return $val;
            });

            $doc = $tradR->getLicenseDocLists($req);
            $docVerifyStatus = $doc->original["data"]["docVerifyStatus"] ?? 0;
            return responseMsgs(true, ["docVerifyStatus" => $docVerifyStatus], remove_null($documents), "010102", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010202", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    #====================[📝📖 APPLICATION DOC UPLOAD | S.L (4.2) 📖📝]========================================================
    public function uploadDocument(Request $req)
    { 
        try {

            $req->validate([
                "applicationId" => "required|digits_between:1,9223372036854775807",
                "document" => "required|mimes:pdf,jpeg,png,jpg,gif",
                "docName" => "required",
                "docCode" => "required",
                "ownerId" => "nullable|digits_between:1,9223372036854775807"
            ]);
            $tradC = $this->_CONTROLLER_TRADE;
            $docUpload = new DocUpload;
            $mWfActiveDocument = new WfActiveDocument();
            $mActiveTradeLicence = $this->_MODEL_ActiveTradeLicence;
            $relativePath = $this->_TRADE_CONSTAINT["TRADE_RELATIVE_PATH"];
            $getLicenceDtls = $mActiveTradeLicence->getLicenceNo($req->applicationId);
            if (!$getLicenceDtls) {
                throw new Exception("Data Not Found!!!!!");
            }
            if ($getLicenceDtls->is_doc_verified) {
                throw new Exception("Document Are Verifed You Cant Reupload Documents");
            }
            $documents = $tradC->getLicenseDocLists($req);
            if (!$documents->original["status"]) {
                throw new Exception($documents->original["message"]);
            };

            $refUlbDtl      = UlbMaster::find($getLicenceDtls->ulb_id);
            $mShortUlbName  = $refUlbDtl->short_name??"";
            if(!$mShortUlbName){
                foreach ((explode(' ', $refUlbDtl->ulb_name)??"") as $val) {
                    $mShortUlbName .= $val[0];
                }
            }
            // $relativePath = trim($relativePath."/".$mShortUlbName,"/");

            $applicationDoc = $documents->original["data"]["listDocs"];
            $applicationDocName = $applicationDoc->implode("docName", ",");
            $applicationDocCode = $applicationDoc->where("docName", $req->docName)->first();
            $applicationCode = $applicationDocCode ? $applicationDocCode["masters"]->implode("documentCode", ",") : "";
            // $mandetoryDoc = $applicationDoc->whereIn("docType",["R","OR"]);

            $ownerDoc = $documents->original["data"]["ownerDocs"];
            $ownerDocsName = $ownerDoc->map(function ($val) {
                $doc = $val["documents"]->map(function ($val1) {
                    return ["docType" => $val1["docType"], "docName" => $val1["docName"], "documentCode" => $val1["masters"]->implode("documentCode", ",")];
                });
                $ownereId = $val["ownerDetails"]["ownerId"];
                $docNames = $val["documents"]->implode("docName", ",");
                return ["ownereId" => $ownereId, "docNames" => $docNames, "doc" => $doc];
            });
            $ownerDocNames = $ownerDocsName->implode("docNames", ",");

            $ownerIds = $ownerDocsName->implode("ownereId", ",");
            $particuler = (collect($ownerDocsName)->where("ownereId", $req->ownerId)->values())->first();

            $ownereDocCode = $particuler ? collect($particuler["doc"])->where("docName", $req->docName)->all() : "";

            $particulerDocCode = collect($ownereDocCode)->implode("documentCode", ",");
            if (!(in_array($req->docName, explode(",", $applicationDocName)) == true || in_array($req->docName, explode(",", $ownerDocNames)) == true)) {
                throw new Exception("Invalid Doc Name Pass");
            }
            if (in_array($req->docName, explode(",", $applicationDocName)) && (empty($applicationDocCode) || !(in_array($req->docCode, explode(",", $applicationCode))))) {
                throw new Exception("Invalid Application Doc Code Pass");
            }
            if (in_array($req->docName, explode(",", $ownerDocNames)) && (!(in_array($req->ownerId, explode(",", $ownerIds))))) {
                throw new Exception("Invalid ownerId Pass");
            }
            if (in_array($req->docName, explode(",", $ownerDocNames)) && ($ownereDocCode && !(in_array($req->docCode, explode(",", $particulerDocCode))))) {
                throw new Exception("Invalid Ownere Doc Code Pass");
            }

            $metaReqs = array();

            $refImageName = $req->docCode;
            $refImageName = $getLicenceDtls->id . '-' . str_replace(' ', '_', $refImageName);
            $document = $req->document;

            $imageName = $docUpload->upload($refImageName, $document, $relativePath);

            $metaReqs['moduleId'] = $this->_MODULE_ID;
            $metaReqs['activeId'] = $getLicenceDtls->id;
            $metaReqs['workflowId'] = $getLicenceDtls->workflow_id;
            $metaReqs['ulbId'] = $getLicenceDtls->ulb_id;
            $metaReqs['relativePath'] = $relativePath;
            $metaReqs['document'] = $imageName;
            $metaReqs['docCode'] = $req->docName; //$req->docCode;

            if (in_array($req->docName, explode(",", $ownerDocNames))) {
                $metaReqs['ownerDtlId'] = $req->ownerId;
            }
            $this->begin();
            #reupload documents;
            $sms = "";
            if ($privDoc = $mWfActiveDocument->getTradeAppByAppNoDocId($getLicenceDtls->id, $getLicenceDtls->ulb_id, collect($req->docName), $getLicenceDtls->workflow_id, $metaReqs['ownerDtlId'] ?? null)) 
            {
                if ($privDoc->verify_status != 2) 
                {
                    // dd("update");
                    $arr["verify_status"] = 0;
                    $arr['relative_path'] = $relativePath;
                    $arr['document'] = $imageName;
                    $arr['doc_code'] = $req->docName;
                    $arr['owner_dtl_id'] = $metaReqs['ownerDtlId'] ?? null;
                    $mWfActiveDocument->docVerifyReject($privDoc->id, $arr);
                } 
                else 
                {
                    // dd("reupload");
                    $mWfActiveDocument->docVerifyReject($privDoc->id, ["status" => 0]);
                    $metaReqs = new Request($metaReqs);
                    $metaReqs =  $mWfActiveDocument->metaReqs($metaReqs);
                    foreach($metaReqs as $key=>$val)
                    {
                        $mWfActiveDocument->$key = $val;
                    }
                    $mWfActiveDocument->save();
                    // $mWfActiveDocument->postDocuments($metaReqs);
                }
                $sms = " Update Successful";
                // return responseMsgs(true, $req->docName . " Update Successful", "", "010201", "1.0", "", "POST", $req->deviceId ?? "");
            }
            else{
                #new documents;
                $metaReqs = new Request($metaReqs);
                $metaReqs =  $mWfActiveDocument->metaReqs($metaReqs);
                foreach($metaReqs as $key=>$val)
                {
                    $mWfActiveDocument->$key = $val;
                }
                $mWfActiveDocument->save();
                // $mWfActiveDocument->postDocuments($metaReqs);
                $sms = " Uploadation Successful";
            }
            $this->commit();
            return responseMsgs(true,  $req->docName . $sms, "", "010201", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            $this->rollback();
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    #====================[📝📖 APPLICATION DOC VERIFICATION | S.L (4.3) 📖📝]========================================================
    public function documentVerify(Request $request)
    {
        $request->validate([
            'id' => 'required|digits_between:1,9223372036854775807',
            'applicationId' => 'required|digits_between:1,9223372036854775807',
            'docRemarks' =>  $request->docStatus == "Rejected" ? 'required|regex:/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/' : "nullable",
            'docStatus' => 'required|in:Verified,Rejected'
        ]);
        try {
            if ((!$this->_COMMON_FUNCTION->checkUsersWithtocken("users"))) {
                throw new Exception("Citizen Not Allowed");
            }
            // Variable Assignments
            $user = Auth()->user();
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $mWfDocument = new WfActiveDocument();
            $workflow_id = $this->_WF_MASTER_Id;
            $rolles = $this->_COMMON_FUNCTION->getUserRoll($userId, $ulbId, $workflow_id);
            if (!$rolles || !$rolles->can_verify_document) {
                throw new Exception("You are Not Authorized For Document Verify");
            }
            $wfDocId = $request->id;
            $applicationId = $request->applicationId;
            $this->begin();
            if ($request->docStatus == "Verified") {
                $status = 1;
            }
            if ($request->docStatus == "Rejected") {
                $status = 2;
            }

            $myRequest = [
                'remarks' => $request->docRemarks,
                'verify_status' => $status,
                'action_taken_by' => $userId
            ];
            $mWfDocument->docVerifyReject($wfDocId, $myRequest);
            $this->commit();
            $tradR = $this->_CONTROLLER_TRADE;
            $doc = $tradR->getLicenseDocLists($request);
            $docVerifyStatus = $doc->original["data"]["docVerifyStatus"] ?? 0;

            return responseMsgs(true, ["docVerifyStatus" => $docVerifyStatus], "", "tc7.1", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            $this->rollBack();
            return responseMsgs(false, $e->getMessage(), "", "tc7.1", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }
    
    #====================[📖 APPLICATION DTL FOR WF | S.L (5.0) 📖]========================================================
    public function getLicenceDtl(Request $request)
    {

        $rules["applicationId"] = "required|digits_between:1,9223372036854775807";
        $validator = Validator::make($request->all(), $rules,);
        if ($validator->fails()) {
            return responseMsg(false, $validator->errors(), $request->all());
        }
        return $this->_REPOSITORY->readLicenceDtl($request);
    }

    #====================[📖 APPLICATION NOTIC DTL | S.L (6.0) 📖]========================================================
    public function getDenialDetails(Request $request)
    {
        return $this->_REPOSITORY->readDenialdtlbyNoticno($request);
    }

    #====================[📖 VALIDATE HOLDING NO | S.L (7.0) 📖]========================================================
    public function validateHoldingNo(Request $request)
    {
        return $this->_REPOSITORY->isvalidateHolding($request);
    }

    #====================[📖 APPLICATION DTL SEARCH | S.L (8.0) 📖]========================================================
    public function searchLicence(Request $request)
    {
        return $this->_REPOSITORY->searchLicenceByNo($request);
    }

    #====================[📖 APPLICATION FULL DTL | S.L (9.0) 📖]========================================================
    public function readApplication(Request $request)
    {
        $rules = [
            "entityValue"   =>  "required",
            "entityName"    =>  "required",
        ];
        $validator = Validator::make($request->all(), $rules,);
        if ($validator->fails()) {
            return responseMsg(false, $validator->errors(), "");
        }
        return $this->_REPOSITORY->readApplication($request);
    }

    #====================[📖 WF DASHBOARD DTL | S.L (10.0) 📖]========================================================
    public function workflowDashordDetails(Request $request)
    {
        try {

            $track = new WorkflowTrack();
            $mWfWorkflow = new WfWorkflow();
            $tradC = $this->_CONTROLLER_TRADE;
            $refUser = Auth()->user();
            $refUserId = $refUser->id;
            $refUlbId = $refUser->ulb_id;
            $refWorkflowId      = $this->_WF_MASTER_Id;
            $userRole = $this->_COMMON_FUNCTION->getUserRoll($refUserId, $refUlbId, $refWorkflowId);
            $wfAllRoles         = $this->_COMMON_FUNCTION->getWorkFlowAllRoles($refUserId, $refUlbId, $refWorkflowId, true);
            $workflow           = $mWfWorkflow->getulbWorkflowId($refWorkflowId, $refUlbId);
            if (!$userRole) {
                throw new Exception("Access Denied! No Role");
            }
            $canView = true;
            if (((!$userRole->forward_role_id ?? false) && !$userRole->backward_role_id)) {
                $canView = false;
            }

            $metaRequest = new Request([
                'workflowId'    => $workflow->id,
                'ulbId'         => $refUlbId,
                'moduleId'      => $this->_MODULE_ID
            ]);
            $dateWiseData = $track->getWfDashbordData($metaRequest)->get();
            $request->request->add(["all" => true]);
            $inboxData = $this->_REPOSITORY->inbox($request);
            $returnData = [
                'canView'               => $canView,
                'userDetails'           => $refUser,
                'roleId'                => $userRole->role_id,
                'roleName'              => $userRole->role_name,
                "shortRole"             => ($this->_TRADE_CONSTAINT['USER-TYPE-SHORT-NAME'][strtoupper($userRole->role_name)]) ?? "N/A",
                'todayForwardCount'     => collect($dateWiseData)->where('sender_role_id', $userRole->role_id)->count(),
                'todayReceivedCount'    => $userRole->is_initiator==false
                                            ?
                                            collect($dateWiseData)->where('receiver_role_id', $userRole->role_id)->count()
                                            :
                                            (
                                                (
                                                    $inboxData->original['data']->where("application_date", Carbon::now()->format('d-m-Y'))->whereNotIn("id",$dateWiseData->pluck("ref_table_id_value"))->count()??0??0
                                                )
                                                +
                                                (
                                                    collect($dateWiseData)->where('receiver_role_id', $userRole->role_id)->count()??0
                                                )
                                            ),
                'pendingApplication'    => $inboxData->original['data']->count() ?? 0,
                'newLicense'            => $inboxData->original['data']->where("application_type_id", 1)->count() ?? 0,
                'renewalLicense'        => $inboxData->original['data']->where("application_type_id", 2)->count() ?? 0,
                'amendmentLicense'      => $inboxData->original['data']->where("application_type_id", 3)->count() ?? 0,
                'surenderLicense'       => $inboxData->original['data']->where("application_type_id", 4)->count() ?? 0
            ];
            return responseMsgs(true, "", remove_null($returnData), "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "01", ".ms", "POST", "");
        }
    }

    #====================[📝📖 APPLICATION ESCALATE DTL | S.L (11.0) 📖📝]========================================================
    public function postEscalate(Request $request)
    {
        return $this->_REPOSITORY->postEscalate($request);
    }

    #====================[📖 ESCALATE APPLICATION LIST | S.L (12.0) 📖]========================================================
    public function specialInbox(ReqInbox $request)
    {
        return $this->_REPOSITORY->specialInbox($request);
    }

    #====================[📖 BTC APPLICATION LIST | S.L (13.0) 📖]========================================================
    public function btcInbox(ReqInbox $request)
    {
        return $this->_REPOSITORY->btcInbox($request);
    }
    
    #====================[📖 PENDING APPLICATION AT CURRENT USER LIST | S.L (14.0) 📖]========================================================
    public function inbox(ReqInbox $request)
    {
        return $this->_REPOSITORY->inbox($request);
    }
    
    #====================[📖 PENDING APPLICATION AT ANOTHER USER LIST | S.L (15.0) 📖]========================================================
    public function outbox(ReqInbox $request)
    {
        return $this->_REPOSITORY->outbox($request);
    }


    #====================[📝📖 BTC THE APPLICATION | S.L (16.0) 📖📝]========================================================
    public function backToCitizen(Request $req)
    {
        $user = Auth()->user();
        $user_id = $user->id;
        $ulb_id = $user->ulb_id;

        $refWorkflowId = $this->_WF_MASTER_Id;
        $role = $this->_COMMON_FUNCTION->getUserRoll($user_id, $ulb_id, $refWorkflowId);
        
        try {
            $req->validate([
                'applicationId' => 'required|digits_between:1,9223372036854775807',
                'currentRoleId' => 'required|integer',
                'comment' => 'required|string'
            ]);
            $activeLicence = $this->_MODEL_ActiveTradeLicence->find($req->applicationId);
            if(!$activeLicence)
            {
                throw new Exception("Application Not Found");
            }
            $req->merge(["workflowId" => $activeLicence->workflow_id]);
            if($activeLicence->is_parked)
            {
                throw new Exception("Application Already BTC");
            }            

            if (!$this->_COMMON_FUNCTION->checkUsersWithtocken("users")) {
                throw new Exception("Citizen Not Allowed");
            }
            if (!$req->senderRoleId) 
            {
                $req->merge(["senderRoleId" => $role->role_id ?? 0]);
            }
            if (!$req->receiverRoleId) 
            {
                $req->merge(["receiverRoleId" => $role->backward_role_id ?? 0]);               
            }
            if($activeLicence->current_role!= $req->senderRoleId )
            {
                throw new Exception("Application Access Forbiden");
            }
            $track = new WorkflowTrack();
            $lastworkflowtrack = $track->select("*")
                ->where('ref_table_id_value', $req->applicationId)
                ->where('module_id', $this->_MODULE_ID)
                ->where('ref_table_dot_id', "active_trade_licences")
                ->whereNotNull('sender_role_id')
                ->orderBy("track_date", 'DESC')
                ->first();
            $this->begin();
            $initiatorRoleId = $activeLicence->initiator_role;
            // $activeLicence->current_role = $initiatorRoleId;
            $activeLicence->is_parked = true;
            $activeLicence->save();

            $metaReqs['moduleId'] = $this->_MODULE_ID;
            $metaReqs['workflowId'] = $activeLicence->workflow_id;
            $metaReqs['refTableDotId'] = $this->_REF_TABLE;
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $metaReqs['trackDate'] = $lastworkflowtrack && $lastworkflowtrack->forward_date ? ($lastworkflowtrack->forward_date . " " . $lastworkflowtrack->forward_time) : Carbon::now()->format('Y-m-d H:i:s');
            $metaReqs['forwardDate'] = Carbon::now()->format('Y-m-d');
            $metaReqs['forwardTime'] = Carbon::now()->format('H:i:s');
            $metaReqs['verificationStatus'] = $this->_TRADE_CONSTAINT["VERIFICATION-STATUS"]["BTC"];#2
            $metaReqs['user_id'] = $user_id;
            $metaReqs['ulb_id'] = $ulb_id;
            $req->merge($metaReqs);
            $track->saveTrack($req);
            $this->commit();
            return responseMsgs(true, "BTC Successfully Done", "", "010111", "1.0", "350ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            $this->rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    # Serial No : 18
    #please not use custome request
    public function postNextLevel(Request $request)
    {
        $user = Auth()->user();
        $user_id = $user->id;
        $ulb_id = $user->ulb_id;

        $refWorkflowId = $this->_WF_MASTER_Id;
        $role = $this->_COMMON_FUNCTION->getUserRoll($user_id, $ulb_id, $refWorkflowId);

        $request->validate([
            "action"        => 'required|in:forward,backward',
            'applicationId' => 'required|digits_between:1,9223372036854775807',
            'senderRoleId' => 'nullable|integer',
            'receiverRoleId' => 'nullable|integer',
            'comment' => ($role->is_initiator ?? false) ? "nullable" : 'required',
        ]);

        try {
            if (!$request->senderRoleId) {
                $request->merge(["senderRoleId" => $role->role_id ?? 0]);
            }
            if (!$request->receiverRoleId) {
                if ($request->action == 'forward') {
                    $request->merge(["receiverRoleId" => $role->forward_role_id ?? 0]);
                }
                if ($request->action == 'backward') {
                    $request->merge(["receiverRoleId" => $role->backward_role_id ?? 0]);
                }
            }


            #if finisher forward then
            if (($role->is_finisher ?? 0) && $request->action == 'forward') {
                $request->merge(["status" => 1]);
                return $this->approveReject($request);
            }

            if (!$this->_COMMON_FUNCTION->checkUsersWithtocken("users")) {
                throw new Exception("Citizen Not Allowed");
            }

            #Trade Application Update Current Role Updation

            $workflowId = WfWorkflow::where('wf_master_id', $refWorkflowId)
                ->where('ulb_id', $ulb_id)
                ->first();
            if (!$workflowId) {
                throw new Exception("Workflow Not Available");
            }

            $licence = $this->_MODEL_ActiveTradeLicence->find($request->applicationId);
            if (!$licence) {
                throw new Exception("Data Not Found");
            }
            // if($licence->is_parked && $request->action=='forward')
            // {
            //      $request->request->add(["receiverRoleId"=>$licence->current_role??0]);
            // }
            $allRolse     = collect($this->_COMMON_FUNCTION->getAllRoles($user_id, $ulb_id, $refWorkflowId, 0, true));

            $initFinish   = $this->_COMMON_FUNCTION->iniatorFinisher($user_id, $ulb_id, $refWorkflowId);
            $receiverRole = array_values(objToArray($allRolse->where("id", $request->receiverRoleId)))[0] ?? [];
            $senderRole   = array_values(objToArray($allRolse->where("id", $request->senderRoleId)))[0] ?? [];
            
            if ($licence->payment_status != 1 && ($role->serial_no  < $receiverRole["serial_no"] ?? 0)) {
                throw new Exception("Payment Not Clear");
            }
            if ((!$role->is_finisher ?? 0) && $request->action == 'backward' && $receiverRole["id"] == $initFinish['initiator']['id']) {
                $request->merge(["currentRoleId" => $request->senderRoleId]);
                return $this->backToCitizen($request);
            }

            if ($licence->current_role != $role->role_id && !$role->is_initiato && (!$licence->is_parked)) {
                throw new Exception("You Have Not Pending This Application");
            }
            if ($licence->is_parked && !$role->is_initiator) {
                throw new Exception("You Aer Not Authorized For Forword BTC Application");
            }

            $sms = "Application BackWord To " . $receiverRole["role_name"] ?? "";

            if ($role->serial_no  < $receiverRole["serial_no"] ?? 0) {
                $sms = "Application Forward To " . $receiverRole["role_name"] ?? "";
            }
            $tradC = $this->_CONTROLLER_TRADE;
            $documents = $tradC->checkWorckFlowForwardBackord($request);

            if ((($senderRole["serial_no"] ?? 0) < ($receiverRole["serial_no"] ?? 0)) && !$documents) {
                if (($role->can_upload_document ?? false) && $licence->is_parked) {
                    throw new Exception("Rejected Document Are Not Uploaded");
                }
                if (($role->can_upload_document ?? false)) {
                    throw new Exception("No Every Madetry Documents are Uploaded");
                }
                if ($role->can_verify_document ?? false) {
                    throw new Exception("No Every Documents are Veryfied Or Madetory Document is Rejected");
                }
                throw new Exception("Not Every Actoin Are Performed");
            }
            if ($role->can_upload_document) {
                if (($role->serial_no < $receiverRole["serial_no"] ?? 0)) {
                    $licence->document_upload_status = true;
                    $licence->pending_status = 1;
                    $licence->is_parked = false;
                }
                if (($role->serial_no > $receiverRole["serial_no"] ?? 0)) {
                    $licence->document_upload_status = false;
                }
            }
            if ($role->can_verify_document) {
                if (($role->serial_no < $receiverRole["serial_no"] ?? 0)) {
                    $licence->is_doc_verified = true;
                    $licence->doc_verified_by = $user_id;
                    $licence->doc_verify_date = Carbon::now()->format("Y-m-d");
                }
                if (($role->serial_no > $receiverRole["serial_no"] ?? 0)) {
                    $licence->is_doc_verified = false;
                }
            }

            $this->begin();
            $licence->max_level_attained = ($licence->max_level_attained < ($receiverRole["serial_no"] ?? 0)) ? ($receiverRole["serial_no"] ?? 0) : $licence->max_level_attained;
            $licence->current_role = $request->receiverRoleId;
            if ($licence->is_parked && $request->action == 'forward') {
                $licence->is_parked = false;
            }
            $licence->update();

            $track = new WorkflowTrack();
            $lastworkflowtrack = $track->select("*")
                ->where('ref_table_id_value', $request->applicationId)
                ->where('module_id', $this->_MODULE_ID)
                ->where('ref_table_dot_id', $this->_REF_TABLE)
                ->whereNotNull('sender_role_id')
                ->orderBy("track_date", 'DESC')
                ->first();


            $metaReqs['moduleId'] = $this->_MODULE_ID;
            $metaReqs['workflowId'] = $licence->workflow_id;
            $metaReqs['refTableDotId'] = $this->_REF_TABLE;
            $metaReqs['refTableIdValue'] = $request->applicationId;
            $metaReqs['user_id'] = $user_id;
            $metaReqs['ulb_id'] = $ulb_id;
            $metaReqs['trackDate'] = $lastworkflowtrack && $lastworkflowtrack->forward_date ? ($lastworkflowtrack->forward_date . " " . $lastworkflowtrack->forward_time) : Carbon::now()->format('Y-m-d H:i:s');
            $metaReqs['forwardDate'] = Carbon::now()->format('Y-m-d');
            $metaReqs['forwardTime'] = Carbon::now()->format('H:i:s');
            $metaReqs['verificationStatus'] = ($request->action == 'forward') ? $this->_TRADE_CONSTAINT["VERIFICATION-STATUS"]["VERIFY"] : $this->_TRADE_CONSTAINT["VERIFICATION-STATUS"]["BACKWARD"];
            $request->merge($metaReqs);
            $track->saveTrack($request);
            $this->commit();
            return responseMsgs(true, $sms, "", "010109", "1.0", "286ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            $this->rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    # Serial No 
    #please not use custome request
    public function approveReject(Request $req)
    {
        try {
            $req->validate([
                "applicationId" => "required",
                "status" => "required",
                "comment" => $req->status == 0 ? "required" : "nullable",
            ]);
            if (!$this->_COMMON_FUNCTION->checkUsersWithtocken("users")) {
                throw new Exception("Citizen Not Allowed");
            }

            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $refWorkflowId = $this->_WF_MASTER_Id;

            $activeLicence = $this->_MODEL_ActiveTradeLicence->find($req->applicationId);

            $role = $this->_COMMON_FUNCTION->getUserRoll($user_id, $ulb_id, $refWorkflowId);

            if(!$activeLicence)
            {
                throw new Exception("Data Not Found!");
            }
            if ($activeLicence->finisher_role != $role->role_id) {
                throw new Exception("Forbidden Access");
            }
            if (!$req->senderRoleId) {
                $req->merge(["senderRoleId" => $role->role_id ?? 0]);
            }
            if (!$req->receiverRoleId) {
                if ($req->action == 'forward') {
                    $req->merge(["receiverRoleId" => $role->forward_role_id ?? 0]);
                }
                if ($req->action == 'backward') {
                    $req->merge(["receiverRoleId" => $role->backward_role_id ?? 0]);
                }
            }
            $track = new WorkflowTrack();
            $lastworkflowtrack = $track->select("*")
                ->where('ref_table_id_value', $req->applicationId)
                ->where('module_id', $this->_MODULE_ID)
                ->where('ref_table_dot_id', "active_trade_licences")
                ->whereNotNull('sender_role_id')
                ->orderBy("track_date", 'DESC')
                ->first();
            $metaReqs['moduleId'] = $this->_MODULE_ID;
            $metaReqs['workflowId'] = $activeLicence->workflow_id;
            $metaReqs['refTableDotId'] = 'active_trade_licences';
            $metaReqs['refTableIdValue'] = $req->applicationId;
            $metaReqs['user_id'] = $user_id;
            $metaReqs['ulb_id'] = $ulb_id;
            $metaReqs['trackDate'] = $lastworkflowtrack && $lastworkflowtrack->forward_date ? ($lastworkflowtrack->forward_date . " " . $lastworkflowtrack->forward_time) : Carbon::now()->format('Y-m-d H:i:s');
            $metaReqs['forwardDate'] = Carbon::now()->format('Y-m-d');
            $metaReqs['forwardTime'] = Carbon::now()->format('H:i:s');
            $metaReqs['verificationStatus'] = ($req->status == 1) ? $this->_TRADE_CONSTAINT["VERIFICATION-STATUS"]["APROVE"] : $this->_TRADE_CONSTAINT["VERIFICATION-STATUS"]["REJECT"];
            $req->merge($metaReqs);

            $this->begin();

            $track->saveTrack($req);
            // Approval
            if ($req->status == 1) {
                $refUlbDtl          = UlbMaster::find($activeLicence->ulb_id);
                // Objection Application replication
                $approvedLicence = $activeLicence->replicate();
                $approvedLicence->setTable('trade_licences');
                $approvedLicence->pending_status = 5;
                $approvedLicence->id = $activeLicence->id;
                $status = $this->giveValidity($approvedLicence);
                if (!$status) {
                    throw new Exception("Some Error Occurs");
                }
                $approvedLicence->save();
                $owneres = $this->_MODEL_ActiveTradeOwner->select("*")
                    ->where("temp_id", $activeLicence->id)
                    ->get();
                foreach ($owneres as $val) {
                    $refOwners = $val->replicate();
                    $refOwners->id = $val->id;
                    $refOwners->setTable('trade_owners');
                    $refOwners->save();
                    $val->delete();
                }
                $activeLicence->delete();
                $licenseNo = $approvedLicence->license_no;
                $msg =  "Application Successfully Approved !!. Your License No Is " . $licenseNo;
                $sms = trade(["application_no" => $approvedLicence->application_no, "licence_no" => $approvedLicence->license_no, "ulb_name" => $refUlbDtl->ulb_name ?? ""], "Application Approved");
            }

            // Rejection
            if ($req->status == 0) {
                // Objection Application replication
                $approvedLicence = $activeLicence->replicate();
                $approvedLicence->setTable('rejected_trade_licences');
                $approvedLicence->id = $activeLicence->id;
                $approvedLicence->pending_status = 4;
                $approvedLicence->save();
                $owneres = $this->_MODEL_ActiveTradeOwner->select("*")
                    ->where("temp_id", $activeLicence->id)
                    ->get();
                foreach ($owneres as $val) {
                    $refOwners = $val->replicate();
                    $refOwners->id = $val->id;
                    $refOwners->setTable('rejected_trade_owners');
                    $refOwners->save();
                    $val->delete();
                }
                $activeLicence->delete();
                $msg = "Application Successfully Rejected !!";
                $sms = trade(["application_no"=>$approvedLicence->application_no,"licence_no"=>$approvedLicence->license_no,"ulb_name"=>$refUlbDtl->ulb_name??""],"Application Reject");
            }
            if (($sms["status"] ?? false)) {
                $tradC = $this->_CONTROLLER_TRADE;
                $owners = $tradC->getAllOwnereDtlByLId($req->applicationId);
                foreach ($owners as $val) {
                    $respons=send_sms($val["mobile_no"],$sms["sms"],$sms["temp_id"]);
                }
            }
            $this->commit();

            return responseMsgs(true, $msg, "", '010811', '01', '474ms-573', 'Post', '');
        } catch (Exception $e) {
            $this->rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    # Serial No : 04
    public function paymentReceipt(Request $request)
    {
        $id = $request->id;
        $transectionId =  $request->transectionId;
        $request->setMethod('POST');
        $request->request->add(["id" => $id, "transectionId" => $transectionId]);
        $rules = [
            "id" => "required|digits_between:1,9223372036854775807",
            "transectionId" => "required|digits_between:1,9223372036854775807",
        ];
        $validator = Validator::make($request->all(), $rules,);
        if ($validator->fails()) {
            return responseMsg(false, $validator->errors(), $request->all());
        }
        return $this->_REPOSITORY->readPaymentReceipt($id, $transectionId);
    }

    # Serial No : 19
    public function provisionalCertificate(Request $request)
    {
        $id = $request->id;
        $request->setMethod('POST');
        $request->request->add(["id" => $id]);
        $rules = [
            "id" => "required|digits_between:1,9223372036854775807",
        ];
        $validator = Validator::make($request->all(), $rules,);
        if ($validator->fails()) {
            return responseMsg(false, $validator->errors(), "");
        }
        return $this->_REPOSITORY->provisionalCertificate($request->id);
    }
    # Serial No : 20
    public function licenceCertificate(Request $request)
    {
        $id = $request->id;
        $request->setMethod('POST');
        $request->request->add(["id" => $id]);
        $rules = [
            "id" => "required|digits_between:1,9223372036854775807",
        ];
        $validator = Validator::make($request->all(), $rules,);
        if ($validator->fails()) {
            return responseMsg(false, $validator->errors(), "");
        }
        return $this->_REPOSITORY->licenceCertificate($request->id);
    }

    # Serial No : 22
    public function addIndependentComment(Request $request)
    {
        return $this->_REPOSITORY->addIndependentComment($request);
    }
    # Serial No : 23
    public function readIndipendentComment(Request $request)
    {
        return $this->_REPOSITORY->readIndipendentComment($request);
    }

    # Serial No : 26
    public function approvedApplication(Request $request)
    {
        return $this->_REPOSITORY->approvedApplication($request);
    }

    
}
