<?php

namespace App\Repository\Trade;

use App\EloquentModels\Common\ModelWard;
use App\MicroServices\DocUpload;
use App\Models\Trade\ActiveTradeNoticeConsumerDtl;
use App\Models\Trade\RejectedTradeNoticeConsumerDtl;
use App\Models\Trade\TradeNoticeConsumerDtl;
use App\Models\Workflows\WfActiveDocument;
use App\Repository\Common\CommonFunction;
use App\Traits\Auth;
use App\Traits\Payment\Razorpay;
use App\Traits\Property\WardPermission;
use App\Traits\Trade\TradeTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TradeNotice implements ITradeNotice
{
    use Auth;               // Trait 
    use WardPermission;
    use Razorpay;
    use TradeTrait;

    /**
     * | Created On-09-02-2023 
     * | Created By-Sandeep Bara
     * | Status (open)
     * |
     * |----------------------
     * | Applying For Trade License
     * | Proper Validation will be applied 
     * | @param Illuminate\Http\Request
     * | @param Request $request
     * | @param response
     */
    protected $_modelWard;
    protected $_parent;
    protected $_wardNo;
    protected $_licenceId;
    protected $_shortUlbName;

    public function __construct()
    {
        $this->_modelWard = new ModelWard();
        $this->_parent = new CommonFunction();
    }
    public function addDenail(Request $request)
    {
        $user = Auth()->user();
        $userId = $user->id;
        $ulbId = $user->ulb_id;
        try {
            $data = array();
            $refWorkflowId = Config::get('workflow-constants.TRADE_NOTICE_ID');
            $refWorkflows       = $this->_parent->iniatorFinisher($userId, $ulbId, $refWorkflowId);
            DB::beginTransaction();
            $denialConsumer = new ActiveTradeNoticeConsumerDtl();
            $denialConsumer->firm_name  = $request->firmName;
            $denialConsumer->owner_name = $request->ownerName;
            $denialConsumer->ward_id    = $request->wardNo;
            $denialConsumer->ulb_id     = $ulbId;
            $denialConsumer->holding_no = $request->holdingNo;
            $denialConsumer->address    = $request->address;
            $denialConsumer->landmark   = $request->landmark;
            $denialConsumer->city       = $request->city;
            $denialConsumer->pin_code    = $request->pinCode;
            $denialConsumer->license_no = $request->licenceNo ?? null;
            $denialConsumer->ip_address = $request->ip();
            $getloc = json_decode(file_get_contents("http://ipinfo.io/"));
            $coordinates = explode(",", $getloc->loc);
            $denialConsumer->latitude   = $coordinates[0]; // latitude
            $denialConsumer->longitude  = $coordinates[1]; // longitude
            $denialConsumer->mobile_no = $request->mobileNo??null;
            $denialConsumer->remarks = $request->comment;
            $denialConsumer->user_id = $userId;

            $denialConsumer->workflow_id  = $refWorkflowId; 
            $denialConsumer->current_role = $refWorkflows['initiator']['id'];
            $denialConsumer->initiator_role = $refWorkflows['finisher']['id'];

            $denialConsumer->save();
            $denial_id = $denialConsumer->id;

            
            $docUpload = new DocUpload;
            $relativePath = Config::get('TradeConstant.TRADE_NOTICE_RELATIVE_PATH');
            $refImageName = $request->docCode;
            $refImageName = $denial_id . '-' . str_replace(' ', '_', $refImageName);
            $document = $request->document;
            $imageName = $docUpload->upload($refImageName, $document, $relativePath);
            if ($denial_id) 
            {
                $denialConsumer->document_path = $relativePath."/".$imageName;
                $denialConsumer->update();

            }
            DB::commit();

            return  responseMsg(true, "Denail Form Submitted Succesfully!", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    public function inbox(Request $request)
    {
        try {
            $data = (array)null;
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $workflow_id = Config::get('workflow-constants.TRADE_NOTICE_ID');
            $role = $this->_parent->getUserRoll($user_id, $ulb_id, $workflow_id);
            $role_id = $role->role_id ?? -1;
            if (!$role) 
            {
                throw new Exception("You Are Not Authorized");
            }

            $wardList = $this->_parent->WardPermission($user_id);
            $data['wardList'] = $wardList;
            $ward_ids = array_map(function ($val) {
                return $val['id'];
            }, $wardList);
            $inputs = $request->all();
            $denila_consumer = ActiveTradeNoticeConsumerDtl::select(
                "active_trade_notice_consumer_dtls.*",
                DB::raw("ulb_ward_masters.ward_name as ward_no")
            )
                ->join("ulb_ward_masters", "ulb_ward_masters.id", "active_trade_notice_consumer_dtls.ward_id");

            if (isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo'] != "ALL") 
            {
                $ward_ids = $inputs["wardNo"];
            }
            if (isset($inputs['key']) && trim($inputs['key'])) 
            {
                $key = trim($inputs['key']);
                $denila_consumer = $denila_consumer->where(function ($query) use ($key) {
                    $query->orwhere('active_trade_notice_consumer_dtls.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.firm_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_trade_notice_consumer_dtls.license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            if (isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate']) {
                $denila_consumer = $denila_consumer
                    ->whereBetween('active_trade_notice_consumer_dtls.created_on::date', [$inputs['formDate'], $inputs['formDate']]);
            }
            $denila_consumer = $denila_consumer
                ->whereIn("active_trade_notice_consumer_dtls.ward_id", $ward_ids)
                ->where("active_trade_notice_consumer_dtls.is_parked", FALSE)
                ->where("active_trade_notice_consumer_dtls.ulb_id", $ulb_id)
                ->where("active_trade_notice_consumer_dtls.current_role", $role_id)
                ->where("active_trade_notice_consumer_dtls.workflow_id", $workflow_id)
                ->where("active_trade_notice_consumer_dtls.status", 1)
                ->orderBy("active_trade_notice_consumer_dtls.created_on", "DESC")
                ->get();
            $data['denila_consumer'] = $denila_consumer;
            return responseMsg(false, "", remove_null($data));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function outbox(Request $request)
    {
        try {
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $refWorkflowId = Config::get('workflow-constants.TRADE_NOTICE_ID');
            $mUserType = $this->_parent->userType($refWorkflowId);
            $ward_permission = $this->_parent->WardPermission($user_id);
            $role = $this->_parent->getUserRoll($user_id, $ulb_id, $refWorkflowId);
            if (!$role) {
                throw new Exception("You Are Not Authorized");
            }
            if ($role->is_initiator || in_array(strtoupper($mUserType), ["JSK", "SUPER ADMIN", "ADMIN", "TL", "PMU", "PM"])) 
            {
                $ward_permission = $this->_modelWard->getAllWard($ulb_id)->map(function ($val) {
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $ward_permission = objToArray($ward_permission);
            } 
            $role_id = $role->role_id;

            $ward_ids = array_map(function ($val) {
                return $val['id'];
            }, $ward_permission);
            $inputs = $request->all();
            $denila_consumer = ActiveTradeNoticeConsumerDtl::select(
                "active_trade_notice_consumer_dtls.*",
                DB::raw("ulb_ward_masters.ward_name as ward_no")
            )
                ->join("ulb_ward_masters", "ulb_ward_masters.id", "active_trade_notice_consumer_dtls.ward_id");

            if (isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo'] != "ALL") 
            {
                $ward_ids = $inputs["wardNo"];
            }
            if (isset($inputs['key']) && trim($inputs['key'])) 
            {
                $key = trim($inputs['key']);
                $denila_consumer = $denila_consumer->where(function ($query) use ($key) {
                    $query->orwhere('active_trade_notice_consumer_dtls.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.firm_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_trade_notice_consumer_dtls.license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            if (isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate']) {
                $denila_consumer = $denila_consumer
                    ->whereBetween('active_trade_notice_consumer_dtls.created_on::date', [$inputs['formDate'], $inputs['formDate']]);
            }
            $denila_consumer = $denila_consumer
                ->whereIn("active_trade_notice_consumer_dtls.ward_id", $ward_ids)
                ->where("active_trade_notice_consumer_dtls.is_parked", FALSE)
                ->where("active_trade_notice_consumer_dtls.ulb_id", $ulb_id)
                ->where("active_trade_notice_consumer_dtls.current_role","<>", $role_id)
                ->where("active_trade_notice_consumer_dtls.workflow_id", $refWorkflowId)
                ->where("active_trade_notice_consumer_dtls.status", 1)
                ->orderBy("active_trade_notice_consumer_dtls.created_on", "DESC")
                ->get();
            $data['denila_consumer'] = $denila_consumer;
            return responseMsg(true, "", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function specialInbox(Request $request)
    {
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refUlbId       = $refUser->ulb_id;
            $refWorkflowId  = Config::get('workflow-constants.TRADE_NOTICE_ID');
            
            $mWardPermission = $this->_parent->WardPermission($refUserId);
            $inputs = $request->all();
            $denila_consumer = ActiveTradeNoticeConsumerDtl::select(
                "active_trade_notice_consumer_dtls.*",
                DB::raw("ulb_ward_masters.ward_name as ward_no")
            )
                ->join("ulb_ward_masters", "ulb_ward_masters.id", "active_trade_notice_consumer_dtls.ward_id");

            if (isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo'] != "ALL") 
            {
                $ward_ids = $inputs["wardNo"];
            }
            if (isset($inputs['key']) && trim($inputs['key'])) 
            {
                $key = trim($inputs['key']);
                $denila_consumer = $denila_consumer->where(function ($query) use ($key) {
                    $query->orwhere('active_trade_notice_consumer_dtls.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.firm_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_trade_notice_consumer_dtls.license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            if (isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate']) {
                $denila_consumer = $denila_consumer
                    ->whereBetween('active_trade_notice_consumer_dtls.created_on::date', [$inputs['formDate'], $inputs['formDate']]);
            }
            $denila_consumer = $denila_consumer
                ->where("active_trade_notice_consumer_dtls.is_escalate", TRUE)
                ->where("active_trade_notice_consumer_dtls.ulb_id", $refUlbId)
                ->where("active_trade_notice_consumer_dtls.status", 1)
                ->orderBy("active_trade_notice_consumer_dtls.created_on", "DESC")
                ->get();
            $data = [
                "wardList" => $mWardPermission,
                "denila_consumer" => $denila_consumer,
            ];
            return responseMsg(true, "", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function btcInbox(Request $request)
    {
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refUlbId       = $refUser->ulb_id;
            $refWorkflowId  = Config::get('workflow-constants.TRADE_NOTICE_ID');
            $mWardPermission = $this->_parent->WardPermission($refUserId);
            $mRole = $this->_parent->getUserRoll($refUserId, $refUlbId, $refWorkflowId);

            if (!$mRole->is_initiator) {
                throw new Exception("You Are Not Authorized For This Action");
            }
            if ($mRole->is_initiator) {
                $mWardPermission = $this->_modelWard->getAllWard($refUlbId)->map(function ($val) {
                    $val->ward_no = $val->ward_name;
                    return $val;
                });
                $mWardPermission = objToArray($mWardPermission);
            }

            $ward_ids = array_map(function ($val) {
                return $val['id'];
            }, $mWardPermission);
            $inputs = $request->all();
            $denila_consumer = ActiveTradeNoticeConsumerDtl::select(
                "active_trade_notice_consumer_dtls.*",
                DB::raw("ulb_ward_masters.ward_name as ward_no")
            )
                ->join("ulb_ward_masters", "ulb_ward_masters.id", "active_trade_notice_consumer_dtls.ward_id");

            if (isset($inputs['wardNo']) && trim($inputs['wardNo']) && $inputs['wardNo'] != "ALL") 
            {
                $ward_ids = $inputs["wardNo"];
            }
            if (isset($inputs['key']) && trim($inputs['key'])) 
            {
                $key = trim($inputs['key']);
                $denila_consumer = $denila_consumer->where(function ($query) use ($key) {
                    $query->orwhere('active_trade_notice_consumer_dtls.holding_no', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.firm_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere("active_trade_notice_consumer_dtls.license_no", 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.owner_name', 'ILIKE', '%' . $key . '%')
                        ->orwhere('active_trade_notice_consumer_dtls.mobile_no', 'ILIKE', '%' . $key . '%');
                });
            }
            if (isset($inputs['formDate']) && isset($inputs['toDate']) && trim($inputs['formDate']) && $inputs['toDate']) {
                $denila_consumer = $denila_consumer
                    ->whereBetween('active_trade_notice_consumer_dtls.created_on::date', [$inputs['formDate'], $inputs['formDate']]);
            }
            $denila_consumer = $denila_consumer
                ->where("active_trade_notice_consumer_dtls.is_parked", TRUE)
                ->whereIn('active_trade_notice_consumer_dtls.ward_id', $ward_ids)
                ->where("active_trade_notice_consumer_dtls.ulb_id", $refUlbId)
                ->where("active_trade_notice_consumer_dtls.status", 1)
                ->orderBy("active_trade_notice_consumer_dtls.created_on", "DESC")
                ->get();
            $data = [
                "wardList" => $mWardPermission,
                "denila_consumer" => $denila_consumer,
            ];
            return responseMsg(true, "", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

     # Serial No : 25
    /**
     * Apply Denail View Data And (Approve Or Reject) By EO
     * | @var data local data storage
     * |+ @var user  login user DATA 
     * |+ @var user_id login user ID
     * |+ @var ulb_id login user ULBID
     * |+ @var workflow_id owrflow id 19 for trade **Config::get('workflow-constants.TRADE_WORKFLOW_ID')
     * |+ @var role_id login user ROLEID **this->_parent->getUserRoll($user_id, $ulb_id,$workflow_id)->role_id??-1
     * | @var mUserType login user sort role name **$this->_parent->userType(workflow_id)
     * |
     * |+ @var denial_details  apply denial detail **this->getDenialDetailsByID($id,$ulb_id)
     * |+ @var denialID =  denial_details->id
     * |     
     */
    public function denialView(Request $request)
    {
        $request->validate([
            "applicationId" => "required",
        ]);
        try {
            $applicationId = $request->applicationId;
            $data = (array)null;
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $workflow_id = Config::get('workflow-constants.TRADE_NOTICE_ID');
            $role_id = $this->_parent->getUserRoll($user_id, $ulb_id, $workflow_id)->role_id ?? -1;
            $mUserType = $this->_parent->userType($workflow_id);

            $denial_details  = $this->getDenialDetailsByID($applicationId);
            $denialID =  $denial_details->id;
            if ($denial_details->status == 5) 
            {
                throw new Exception("Notice No Already Generated " . $denial_details->notice_no);
            } 
            if ($denial_details->status == 4) 
            {
                throw new Exception("Denial Request Rejected");
            }
            // $denial_details->file_name = !empty(trim($denial_details->file_name)) ? $this->readDocumentPath($denial_details->file_name) : null;
            // if ($request->getMethod() == 'GET') 
            {
                $data["denial_details"] = $denial_details;
                return responseMsg(true, "", remove_null($data));
            } 
            // elseif ($request->getMethod() == 'POST') 
            // {
            //     $denial_consumer = ActiveTradeNoticeConsumerDtl::find($denialID);

            //     $nowdate = Carbon::now()->format('Y-m-d');
            //     $timstamp = Carbon::now()->format('Y-m-d H:i:s');
            //     $regex = '/^[a-zA-Z1-9][a-zA-Z1-9\.\s]+$/';
            //     $rules = [];
            //     $message = [];
            //     $rules["btn"] = "required|in:approve,reject";
            //     $message["btn.in"] = "btn Value In approve,reject";
            //     $rules["comment"] = "required|min:10|regex:$regex";
            //     $validator = Validator::make($request->all(), $rules, $message);
            //     if ($validator->fails()) 
            //     {
            //         return responseMsg(false, $validator->errors(), $request->all());
            //     }
            //     if ($mUserType != "EO") 
            //     {
            //         throw new Exception("You Are Not Authorize For Approve Or Reject Denial Detail");
            //     }
            //     DB::beginTransaction();
            //     # Approve Application
            //     $res = [];
            //     if ($request->btn == "approve") 
            //     {
            //         $denial_consumer->status = 5;
            //         $denial_consumer->notice_date  = $nowdate;
            //         $noticeNO = "NOT/" . date('dmy') . $denialID;
            //         $denial_consumer->notice_no = $noticeNO;
            //         $denial_consumer->update();
            //         $res["noticeNo"] = $noticeNO;
            //         $res["sms"] = "Notice No Successfuly Generated";
            //     }
            //     if ($request->btn == "reject") 
            //     {
            //         $denial_consumer->status = 4;
            //         $denial_consumer->update();
            //         $res["noticeNo"] = "";
            //         $res["sms"] = "Denail Apply Rejected";
            //     }
            //     DB::commit();
            //     return responseMsg(true, $res["sms"], remove_null($res["noticeNo"]));
            // }
        } 
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }

    public function approveReject(Request $req)
    {
        try {
            $req->validate([
                "applicationId" => "required",
                "status" => "required"
            ]);
            $application = ActiveTradeNoticeConsumerDtl::find($req->applicationId);

            if(!$application)
            {
                throw new Exception("Data Not Found");
            }
            if ($application->finisher_role != $req->roleId) 
            {
                return responseMsg(false, "Forbidden Access", "");
            }
            DB::beginTransaction();

            // Approval
            if ($req->status == 1) 
            {
                // Objection Application replication
                $approvedApplication = $application->replicate();
                $approvedApplication->setTable('trade_notice_consumer_dtls');
                $approvedApplication->id = $application->id;
                $approvedApplication->status=5;
                $approvedApplication->notice_no = $this->generateNoticNo($approvedApplication->id);
                $approvedApplication->save();
                $application->forceDelete();

                $msg =  "Application Successfully Approved !!";
            }

            // Rejection
            if ($req->status == 0) 
            {
                // Objection Application replication
                $rejectedApplication = $application->replicate();
                $rejectedApplication->setTable('rejected_trade_notice_consumer_dtls');
                $rejectedApplication->id = $application->id;
                $approvedApplication->status = 4;
                $rejectedApplication->save();
                $application->forcedelete();
                $msg = "Application Successfully Rejected !!";
            }
            DB::commit();

            return responseMsgs(true, $msg, "", '010811', '01', '474ms-573', 'Post', '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "", '010811', '01', '474ms-573', 'Post', '');
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

    public function getDenialDetailsByID($applicationId)
    {
        $application = ActiveTradeNoticeConsumerDtl::find($applicationId);
        if(!$application)
        {
            $application = TradeNoticeConsumerDtl::find($applicationId);
        }
        if(!$application)
        {
            $application = RejectedTradeNoticeConsumerDtl::find($applicationId);
        }
        return $application;
    }
}