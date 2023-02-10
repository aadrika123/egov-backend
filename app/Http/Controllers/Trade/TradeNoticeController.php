<?php

namespace App\Http\Controllers\Trade;

use App\EloquentModels\Common\ModelWard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trade\ReqApplyDenail;
use App\Models\Trade\ActiveTradeNoticeConsumerDtl;
use App\Models\Workflows\WfWorkflow;
use App\Models\WorkflowTrack;
use App\Repository\Common\CommonFunction;
use App\Repository\Trade\ITradeNotice;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TradeNoticeController extends Controller
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
    public function __construct(ITradeNotice $TradeRepository)
    {
        $this->Repository = $TradeRepository;
        $this->_modelWard = new ModelWard();
        $this->_parent = new CommonFunction();
    }
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
            return $this->Repository->addDenail($request);
        } catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }
    public function inbox(Request $request)
    {
       return  $this->Repository->inbox($request);
    }
    public function outbox(Request $request)
    {
        return  $this->Repository->outbox($request);
    }
    public function specialInbox(Request $request)
    {
        return  $this->Repository->specialInbox($request);
    }
    public function btcInbox(Request $request)
    {
        return  $this->Repository->btcInbox($request);
    }
    public function postNextLevel(Request $request)
    {

        $request->validate([
            'applicationId' => 'required|integer',
            'senderRoleId' => 'required|integer',
            'receiverRoleId' => 'required|integer',
            'comment' => 'required',
        ]);

        try {
            // Trade Notice Application Update Current Role Updation
            $user = Auth()->user();
            $user_id = $user->id;
            $ulb_id = $user->ulb_id;
            $refWorkflowId = Config::get('workflow-constants.TRADE_NOTICE_ID');
            $workflowId = WfWorkflow::where('id', $refWorkflowId)
                ->where('ulb_id', $ulb_id)
                ->first();
            if (!$workflowId) 
            {
                throw new Exception("Workflow Not Available");
            }
            
            $application = ActiveTradeNoticeConsumerDtl::find($request->applicationId);
            if(!$application)
            {
                throw new Exception("Data Not Found");
            }
            $allRolse = collect($this->_parent->getAllRoles($user_id,$ulb_id,$refWorkflowId,0,true));
            $receiverRole = array_values(objToArray($allRolse->where("id",$request->receiverRoleId)))[0]??[];
            $role = $this->_parent->getUserRoll($user_id,$ulb_id,$refWorkflowId);
            
            
            if($application->current_role != $role->role_id)
            {
                throw new Exception("You Have Not Pending This Application");
            }
            $sms ="Application BackWord To ".$receiverRole["role_name"]??"";
            
            if($role->serial_no  < $receiverRole["serial_no"]??0)
            {
                $sms ="Application Forward To ".$receiverRole["role_name"]??"";
            }
            

            DB::beginTransaction();

            $application->max_level_attained = ($application->max_level_attained < ($receiverRole["serial_no"]??0)) ? ($receiverRole["serial_no"]??0) : $application->max_level_attained;
            $application->current_role = $request->receiverRoleId;
            $application->update();


            $metaReqs['moduleId'] = Config::get('module-constants.TRADE_MODULE_ID');
            $metaReqs['workflowId'] = $application->workflow_id;
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
    public function denialView(Request $request)
    {
        return  $this->Repository->denialView($request);
    }
    public function approveReject(Request $request)
    {
        return  $this->Repository->approveReject($request);
    }
}