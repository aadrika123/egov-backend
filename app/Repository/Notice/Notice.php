<?php

namespace App\Repository\Notice;

use App\EloquentModels\Common\ModelWard;
use App\MicroServices\DocUpload;
use App\Models\Notice\NoticeApplication;
use App\Repository\Common\CommonFunction;
use App\Traits\Auth;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Created By Sandeep Bara
 * Date 2023-03-027
 * Notice Module
 */

 class Notice implements INotice
 {
    use Auth;
    use Workflow;

    private $_COMMON_FUNCTION;
    private $_WF_MASTER_ID;
    protected $_GENERAL_NOTICE_WF_MASTER_Id;
    protected $_PAYMENT_NOTICE_WF_MASTER_Id;
    protected $_ILLEGAL_OCCUPATION_WF_MASTER_Id;
    protected $_MODULE_ID;
    protected $_REF_TABLE;
    protected $_NOTICE_CONSTAINT;
    protected $_DOC_PATH;
    protected $_NOTICE_TYPE;
    protected $_MODULE_CONSTAINT;
    public function __construct()
    {
        $this->_COMMON_FUNCTION = new CommonFunction();
        $this->_GENERAL_NOTICE_WF_MASTER_Id = Config::get('workflow-constants.GENERAL_NOTICE_MASTER_ID');
        $this->_PAYMENT_NOTICE_WF_MASTER_Id = Config::get('workflow-constants.PAYMENT_NOTICE_MASTER_ID');
        $this->_ILLEGAL_OCCUPATION_WF_MASTER_Id = Config::get('workflow-constants.ILLEGAL_OCCUPATION_NOTICE_MASTER_ID');
        $this->_MODULE_CONSTAINT=Config::get('module-constants');
        $this->_MODULE_ID = Config::get('module-constants.NOTICE_MASTER_ID');
        $this->_NOTICE_CONSTAINT = Config::get("NoticeConstaint");
        $this->_REF_TABLE = $this->_NOTICE_CONSTAINT["NOTICE_REF_TABLE"]??null;
        $this->_DOC_PATH = $this->_NOTICE_CONSTAINT["NOTICE_RELATIVE_PATH"]??null;
        $this->_NOTICE_TYPE = $this->_NOTICE_CONSTAINT["NOTICE-TYPE"]??null;
        $this->_WF_MASTER_ID=null;
        
    }

    public function add(Request $request)
    {
        $user = Auth()->user();
        $userId = $user->id;
        $ulbId = $user->ulb_id;
        $notice_type_id = null;
        try{
            $data = array();
            if($request->noticeType==1)
            {
                $this->_WF_MASTER_ID = $this->_GENERAL_NOTICE_WF_MASTER_Id;                
            }
            elseif($request->noticeType==2)
            {
                $this->_WF_MASTER_ID = $this->_GENERAL_NOTICE_WF_MASTER_Id;
            }
            elseif($request->noticeType==3)
            {
                $this->_WF_MASTER_ID = $this->_PAYMENT_NOTICE_WF_MASTER_Id;
            }
            elseif($request->noticeType==4)
            {
                $this->_WF_MASTER_ID = $this->_ILLEGAL_OCCUPATION_WF_MASTER_Id;
            }
            $notice_for_module_id=$this->_NOTICE_CONSTAINT["NOTICE-MODULE"][strtoupper($request->moduleName)]??null;
            if(!$this->_WF_MASTER_ID)
            {
                throw new Exception("Workflow Not Avalable");
            }
            if(!$notice_for_module_id)
            {
                throw new Exception("Enter Valide Module Name");
            }
            $notice_type_id = $request->noticeType??NULL;
            $notice_type = $this->_NOTICE_CONSTAINT["NOTICE-TYPE-BY-ID"][$notice_type_id]??null;
            $refWorkflows  = $this->_COMMON_FUNCTION->iniatorFinisher($userId, $ulbId, $this->_WF_MASTER_ID);
            
            DB::beginTransaction();
            $noticeApplication = new NoticeApplication();
            $noticeApplication->notice_type_id  = $notice_type_id;
            $noticeApplication->notice_for_module_id  = $notice_for_module_id;
            $noticeApplication->application_id  = $request->applicationId??NULL;
            if($request->applicationId && $request->moduleId)
            {
                $noticeApplication->module_id  = $request->moduleId;
                $noticeApplication->module_type  = $request->moduleType;
            }
            $noticeApplication->firm_name       = $request->firmName;
            $noticeApplication->ptn_no          = $request->ptnNo;
            $noticeApplication->holding_no      = $request->holdingNo;
            $noticeApplication->license_no      = $request->licenseNo;
            $noticeApplication->served_to       = $request->servedTo;
            $noticeApplication->address         = $request->address;
            $noticeApplication->locality        = $request->locality;
            $noticeApplication->mobile_no       = $request->mobileNo;
            $noticeApplication->owner_name      = $request->ownerName;
            $noticeApplication->notice_content  = $request->noticeDescription;
            $noticeApplication->initater_role   = $refWorkflows["initiator"]["id"];
            $noticeApplication->current_role    = $refWorkflows["initiator"]["id"];
            $noticeApplication->finiser_role    = $refWorkflows["finisher"]["id"];
            $noticeApplication->workflow_id     = $this->_WF_MASTER_ID;
            $noticeApplication->user_id         = $userId;
            $noticeApplication->ulb_id          = $ulbId;
            
            $noticeApplication->save();
            $notice_id = $noticeApplication->id;
            if ($notice_id && $request->document) 
            {
                $docUpload = new DocUpload;
                $refImageName = $notice_type;
                $refImageName = $notice_id . '-' . str_replace(' ', '_', $refImageName);
                $document = $request->document;
                $imageName = $docUpload->upload($refImageName, $document, $this->_DOC_PATH);

                $noticeApplication->documents = $this->_DOC_PATH."/".$imageName;
                $noticeApplication->update();

            }
            $message="Notice Apply Successfully";
            if($noticeApplication->initater_role==$noticeApplication->finiser_role )
            {
                $metaReqs["applicationId"] = $notice_id;
                $metaReqs["status"] = 1;
                $metaReqs = new Request($metaReqs);
                $response = $this->approveReject($metaReqs);
                $message = $response->original["message"];
                if(!$response->original["status"])
                {
                    throw new Exception($message);
                }
            }
            
            DB::commit();
            return  responseMsg(true, $message, $data);

        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function noticeList(Request $request)
    {        
        try{
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            if(!in_array(strtoupper($request->moduleName),$this->_NOTICE_CONSTAINT["MODULE-TYPE"]))
            {
                throw new Exception("Invalide Module");
            }
            $notice_for_module_id=$this->_NOTICE_CONSTAINT["NOTICE-MODULE"][strtoupper($request->moduleName)]??null;
            $request->request->add(["moduleId"=>$notice_for_module_id]);   

            $notice = NoticeApplication::select(
                        "notice_applications.id",
                        "notice_applications.notice_type_id",
                        "notice_applications.notice_no",
                        "notice_applications.notice_date",
                        "notice_applications.notice_state",
                        "notice_applications.application_id",
                        "notice_applications.module_id",
                        "notice_applications.module_type",
                        "notice_applications.firm_name",
                        "notice_applications.ptn_no",
                        "notice_applications.holding_no",
                        "notice_applications.license_no",                        
                        "notice_applications.served_to",
                        "notice_applications.address",
                        "notice_applications.locality",
                        "notice_applications.mobile_no",
                        "notice_applications.notice_content",
                        "notice_applications.owner_name",
                        "notice_applications.documents",
                        "notice_applications.status",
                        "notice_type_masters.notice_type"
                    )
                    ->join("notice_type_masters","notice_type_masters.id","notice_applications.notice_type_id")
                    ->where("notice_applications.ulb_id",$ulb_id)
                    ->where("notice_applications.status","<>",0)
                    ->where("notice_applications.notice_for_module_id",$request->moduleId)
                    ->get();
            $data["application"] = $notice;
            $data["total_notice"] = $notice->count();
            $data["total_aproved_notice"] = $notice->where("status",5)->count();
            $data["total_rejected_notice"] = $notice->where("status",4)->count();
            $data["total_general_notice"] = $notice->where("notice_type_id",($this->_NOTICE_TYPE["GENERAL NOTICE"]??0))->count();
            $data["total_denial_notice"] = $notice->where("notice_type_id",($this->_NOTICE_TYPE["DENIAL NOTICE"]??0))->count();
            $data["total_payment_notice"] = $notice->where("notice_type_id",($this->_NOTICE_TYPE["PAYMENT RELATED NOTICE"]??0))->count();
            $data["total_illegal_notice"] = $notice->where("notice_type_id",($this->_NOTICE_TYPE["ILLEGAL OCCUPATION NOTICE"]??0))->count();
            return responseMsg(true, "",  remove_null($data));
        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    public function noticeView(Request $request)
    {
        try{
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $notice = NoticeApplication::select(
                "notice_applications.id",
                "notice_applications.notice_type_id",
                "notice_applications.notice_no",
                "notice_applications.notice_date",
                "notice_applications.notice_state",
                "notice_applications.application_id",
                "notice_applications.module_id",
                "notice_applications.module_type",
                "notice_applications.firm_name",
                "notice_applications.ptn_no",
                "notice_applications.holding_no",
                "notice_applications.license_no",                        
                "notice_applications.served_to",
                "notice_applications.address",
                "notice_applications.locality",
                "notice_applications.mobile_no",
                "notice_applications.notice_content",
                "notice_applications.owner_name",
                "notice_applications.documents",
                "notice_applications.status",
                "notice_type_masters.notice_type",
                DB::raw("caset(notice_applications.created_at,date) as apply_date"),
            )
            ->join("notice_type_masters","notice_type_masters.id","notice_applications.notice_type_id")
            ->where("notice_applications.ulb_id",$ulb_id)
            ->where("notice_applications.status","<>",0)
            ->where("notice_applications.notice_for_module_id",$request->moduleId)
            ->first();

    
        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
        

    public function approveReject(Request $req)
    {
        try {
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $req->validate([
                "applicationId" => "required",
                "status" => "required"
            ]);
            $application = NoticeApplication::find($req->applicationId);           
            if(!$application)
            {
                throw new Exception("Data Not Found");
            }
            $this->_WF_MASTER_ID = $application->workflow_id;
            $role = $this->_COMMON_FUNCTION->getUserRoll($user_id,$ulb_id,$this->_WF_MASTER_ID);
            if ($application->finisher_role != $role->role_id) 
            {
                return responseMsg(false, "Forbidden Access", "");
            }
            DB::beginTransaction();

            // Approval
            if ($req->status == 1) 
            {
                // Objection Application replication
                $application->status=5;
                $application->notice_no = $this->generateNoticNo($application->id);
                $application->notice_date = Carbon::now()->format("Y-m-d");
                $application->update();
                $msg =  "Notice Successfully Generated !!. Your Notice No. ".$application->notice_no;
            }

            // Rejection
            if ($req->status == 0) 
            {
                // Objection Application replication
                $application->status = 4;
                $application->update();
                $msg = "Application Successfully Rejected !!";
            }
            DB::commit();
            return responseMsg(true, $msg, "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * |-------------------------------------------------
     * |  NOT/      |    11222        |  1234          |
     * |    (4)     |   date('dmy')   | uniqueNo       |
     * |________________________________________________
     */
    public function generateNoticNo($applicationId)
    {
        $noticeNO = "NOT/" . date('dmy') . $applicationId;
        return $noticeNO;
    }
 }