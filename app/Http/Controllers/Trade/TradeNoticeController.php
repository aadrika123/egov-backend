<?php

namespace App\Http\Controllers\Trade;

use App\EloquentModels\Common\ModelWard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Trade\ReqApplyDenail;
use App\Repository\Common\CommonFunction;
use App\Repository\Trade\ITradeNotice;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

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
    public function denialView(Request $request)
    {
        return  $this->Repository->denialView($request);
    }
    public function approveReject(Request $request)
    {
        return  $this->Repository->approveReject($request);
    }
}