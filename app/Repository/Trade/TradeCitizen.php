<?php

/**
 * | Created On-22-12-2022 
 * | Created By-Sandeep Bara
 * --------------------------------------------------------------------------------------
 * | Controller regarding with Trade Module From Counter Side 
 */

namespace App\Repository\Trade;

use App\EloquentModels\Common\ModelWard;
use App\Models\Trade\ActiveLicence;
use App\Models\Trade\ExpireLicence;
use App\Models\Trade\TradeFineRebetDetail;
use App\Models\Trade\TradeParamItemType;
use App\Models\Trade\TradeRazorPayRequest;
use App\Models\Trade\TradeRazorPayResponse;
use App\Models\Trade\TradeTransaction;
use App\Models\UlbMaster;
use App\Models\UlbWardMaster;
use App\Repository\Common\CommonFunction;
use App\Traits\Auth;
use App\Traits\Payment\Razorpay;
use App\Traits\Property\WardPermission;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Models\Trade\ActiveTradeLicence;
use App\Models\Trade\TradeFineRebete;

class TradeCitizen implements ITradeCitizen
{
    use Auth;               // Trait Used added by sandeep bara date 17-09-2022
    use WardPermission;
    use Razorpay;

    protected $_counter;
    protected $_modelWard;
    protected $_parent;

    protected $_metaData;
    protected $_queryRunTime;
    protected $_apiId;


    public function __construct()
    {
        $this->_modelWard = new ModelWard();
        $this->_parent = new CommonFunction();
        $this->_counter = new Trade;
        $this->_metaData = [
            "apiId" => $this->_apiId,
            "version" => 1.1,
            'queryRunTime' => $this->_queryRunTime,
        ];
    }
    public function addRecord(Request $request)
    {
        $this->_metaData["apiId"] = "c2";
        $this->_metaData["queryRunTime"] = 2.48;
        $this->_metaData["action"]    = $request->getMethod();
        $this->_metaData["deviceId"] = $request->ip();
        try {
            $refUser            = Auth()->user();
            $refUserId          = $refUser->id;
            $refUlbId           = $request->ulbId;
            $refWorkflowId      = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
            $mUserType          = $this->_parent->userType($refWorkflowId);
            $mApplicationTypeId = Config::get("TradeConstant.APPLICATION-TYPE." . $request->applicationType);
            if (!in_array(strtoupper($mUserType), ["ONLINE"])) {
                throw new Exception("You Are Not Authorized For This Action. Please Apply From Counter");
            }
            if ($mApplicationTypeId != 1) {
                $mOldLicenceId = $request->id;
                $nextMonth = Carbon::now()->addMonths(1)->format('Y-m-d');
                $refOldLicece = ActiveLicence::find($mOldLicenceId);
                if (!$refOldLicece) {
                    throw new Exception("Old Licence Not Found");
                }
                if ($refOldLicece->valid_upto > $nextMonth) {
                    throw new Exception("Licence Valice Upto " . $refOldLicece->valid_upto);
                }
                if ($refOldLicece->pending_status != 5) {
                    throw new Exception("Application Aready Apply Please Track  " . $refOldLicece->application_no);
                }
                if ($refUlbId != $refOldLicece->ulb_id) {
                    throw new Exception("Application ulb Deffrence " . $refOldLicece->application_no);
                }
            }
            DB::beginTransaction();
            $response = $this->_counter->addRecord($request);
            if (!$response->original["status"]) {
                throw new Exception($response->original["message"]);
            }
            DB::commit();
            return responseMsgs(
                true,
                $response->original["message"],
                $response->original["data"],
                $this->_metaData["apiId"],
                $this->_metaData["version"],
                $this->_metaData["queryRunTime"],
                $this->_metaData["action"],
                $this->_metaData["deviceId"]
            );
        } catch (Exception $e) {
            return responseMsgs(
                false,
                $e->getMessage(),
                $request->all(),
                $this->_metaData["apiId"],
                $this->_metaData["version"],
                $this->_metaData["queryRunTime"],
                $this->_metaData["action"],
                $this->_metaData["deviceId"]
            );
        }
    }
    public function razorPayResponse($args)
    {
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id ?? $args["userId"];
            $refUlbId       = $refUser->ulb_id ?? $args["ulbId"];
            $refWorkflowId  = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
            $refWorkflows   = $this->_parent->iniatorFinisher($refUserId, $refUlbId, $refWorkflowId);
            $refNoticeDetails = null;
            $refDenialId    = null;
            $refUlbDtl      = UlbMaster::find($refUlbId);
            $refUlbName     = explode(' ', $refUlbDtl->ulb_name);
            $mNowDate       = Carbon::now()->format('Y-m-d');
            $mTimstamp      = Carbon::now()->format('Y-m-d H:i:s');
            $mDenialAmount  = 0;
            $mPaymentStatus = 1;
            $mNoticeDate    = null;
            $mShortUlbName  = "";
            $mWardNo        = "";
            foreach ($refUlbName as $val) {
                $mShortUlbName .= $val[0];
            }

            #-----------valication-------------------   
            $RazorPayRequest = TradeRazorPayRequest::select("*")
                ->where("order_id", $args["orderId"])
                ->where("licence_id", $args["id"])
                ->where("status", 2)
                ->first();
            if (!$RazorPayRequest) {
                throw new Exception("Data Not Found");
            }
            $refLecenceData = ActiveLicence::find($args["id"]);
            $licenceId = $args["id"];
            $refLevelData = $this->_counter->getWorkflowTrack($licenceId); //TradeLevelPending::getLevelData($licenceId);
            if (!$refLecenceData) {
                throw new Exception("Licence Data Not Found !!!!!");
            } elseif ($refLecenceData->application_type_id == 4) {
                throw new Exception("Surender Application Not Pay Anny Amount");
            } elseif (in_array($refLecenceData->payment_status, [1, 2])) {
                throw new Exception("Payment Already Done Of This Application");
            }
            if ($refNoticeDetails = $this->_counter->readNotisDtl($refLecenceData->id)) {
                $refDenialId = $refNoticeDetails->dnialid;
                $mNoticeDate = date("Y-m-d", strtotime($refNoticeDetails['created_on'])); //notice date 
            }

            $ward_no = UlbWardMaster::select("ward_name")
                ->where("id", $refLecenceData->ward_mstr_id)
                ->first();
            $mWardNo = $ward_no['ward_name'];

            #-----------End valication-------------------

            #-------------Calculation-----------------------------                
            $args['areaSqft']            = (float)$refLecenceData->area_in_sqft;
            $args['application_type_id'] = $refLecenceData->application_type_id;
            $args['firmEstdDate'] = !empty(trim($refLecenceData->valid_from)) ? $refLecenceData->valid_from : $refLecenceData->apply_date;
            if ($refLecenceData->application_type_id == 1) {
                $args['firmEstdDate'] = $refLecenceData->establishment_date;
            }
            $args['tobacco_status']      = $refLecenceData->tobacco_status;
            $args['licenseFor']          = $refLecenceData->licence_for_years;
            $args['nature_of_business']  = $refLecenceData->nature_of_bussiness;
            $args['noticeDate']          = $mNoticeDate;
            $chargeData = $this->_counter->cltCharge($args);
            if ($chargeData['response'] == false || round($args['amount']) != round($chargeData['total_charge'])) {
                throw new Exception("Payble Amount Missmatch!!!");
            }

            $transactionType = Config::get('TradeConstant.APPLICATION-TYPE-BY-ID.' . $refLecenceData->application_type_id);

            $totalCharge = $chargeData['total_charge'];
            $mDenialAmount = $chargeData['notice_amount'];
            #-------------End Calculation-----------------------------
            #-------- Transection -------------------
            DB::beginTransaction();

            $RazorPayResponse = new TradeRazorPayResponse();
            $RazorPayResponse->licence_id   = $RazorPayRequest->licence_id;
            $RazorPayResponse->request_id   = $RazorPayRequest->id;
            $RazorPayResponse->amount       = $args['amount'];
            $RazorPayResponse->merchant_id  = $args['merchantId'] ?? null;
            $RazorPayResponse->order_id     = $args["orderId"];
            $RazorPayResponse->payment_id   = $args["paymentId"];
            $RazorPayResponse->save();

            $RazorPayRequest->status = 1;
            $RazorPayRequest->update();

            $Tradetransaction = new TradeTransaction();
            $Tradetransaction->temp_id          = $licenceId;
            $Tradetransaction->response_id      = $RazorPayResponse->id;
            $Tradetransaction->ward_id          = $refLecenceData->ward_mstr_id;
            $Tradetransaction->tran_type        = $transactionType;
            $Tradetransaction->tran_date        = $mNowDate;
            $Tradetransaction->payment_mode     = "Online";
            $Tradetransaction->paid_amount      = $totalCharge;
            $Tradetransaction->penalty          = $chargeData['penalty'] + $mDenialAmount + $chargeData['arear_amount'];
            $Tradetransaction->emp_dtl_id       = $refUserId;
            $Tradetransaction->created_at       = $mTimstamp;
            $Tradetransaction->ip_address       = '';
            $Tradetransaction->ulb_id           = $refUlbId;
            $Tradetransaction->save();
            $transaction_id                     = $Tradetransaction->id;
            $Tradetransaction->transaction_no   = $args["transactionNo"]; //$this->createTransactionNo($transaction_id);//"TRANML" . date('d') . $transaction_id . date('Y') . date('m') . date('s');
            $Tradetransaction->update();

            $TradeFineRebet = new TradeFineRebete();
            $TradeFineRebet->tran_id = $transaction_id;
            $TradeFineRebet->type      = 'Delay Apply License';
            $TradeFineRebet->amount         = $chargeData['penalty'];
            $TradeFineRebet->created_at     = $mTimstamp;
            $TradeFineRebet->save();

            $mDenialAmount = $mDenialAmount + $chargeData['arear_amount'];
            if ($mDenialAmount > 0) {
                $TradeFineRebet2 = new TradeFineRebete;
                $TradeFineRebet2->tran_id = $transaction_id;
                $TradeFineRebet2->type      = 'Denial Apply';
                $TradeFineRebet2->amount         = $mDenialAmount;
                $TradeFineRebet2->created_at     = $mTimstamp;
                $TradeFineRebet2->save();
            }

            if ($mPaymentStatus == 1 && $refLecenceData->document_upload_status = 1 && $refLecenceData->pending_status = 0 && !$refLevelData) {
                $refLecenceData->current_user_id = $refWorkflows['initiator']['id'];
                $refLecenceData->pending_status  = 2;
                $args["sender_role_id"] = $refWorkflows['initiator']['id'];
                $args["receiver_role_id"] = $refWorkflows['initiator']['forward_id'];
                $args["citizen_id"] = $refUserId;;
                $args["ref_table_dot_id"] = "active_licences";
                $args["ref_table_id_value"] = $licenceId;
                $args["workflow_id"] = $refWorkflowId;
                $args["module_id"] = Config::get('TradeConstant.MODULE-ID');

                $tem =  $this->_counter->insertWorkflowTrack($args);
            }

            $provNo = $this->_counter->createProvisinalNo($mShortUlbName, $mWardNo, $licenceId);
            $refLecenceData->provisional_license_no = $provNo;
            $refLecenceData->payment_status         = $mPaymentStatus;
            $refLecenceData->save();

            if ($refNoticeDetails) {
                $this->_counter->updateStatusFine($refDenialId, $chargeData['notice_amount'], $licenceId, 1); //update status and fineAmount                     
            }
            DB::commit();
            #----------End transaction------------------------
            #----------Response------------------------------
            $res['transactionId'] = $transaction_id;
            $res['paymentReceipt'] = config('app.url') . "/api/trade/paymentReceipt/" . $licenceId . "/" . $transaction_id;
            return responseMsg(true, "", $res);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $args);
        }
    }

    # Serial No : 27
    public function citizenApplication(Request $request)
    {
        // try {

        $refUser        = Auth()->user();
        $refUserId      = $refUser->id;
        $refWorkflowId      = Config::get('workflow-constants.TRADE_WORKFLOW_ID');

        $licence = ActiveTradeLicence::select(
            "active_trade_licences.id",
            "active_trade_licences.application_no",
            "active_trade_licences.provisional_license_no",
            "active_trade_licences.license_no",
            "active_trade_licences.document_upload_status",
            "active_trade_licences.payment_status",
            "active_trade_licences.pending_status",
            "active_trade_licences.firm_name",
            "active_trade_licences.application_date",
            "active_trade_licences.apply_from",
            "active_trade_licences.application_type_id",
            "owner.owner_name",
            "owner.guardian_name",
            "owner.mobile_no",
            "owner.email_id",
            DB::raw("'active' as license_type"),
        )
            ->join(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                 STRING_AGG(guardian_name,',') AS guardian_name,
                                 STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                 STRING_AGG(email_id,',') AS email_id,
                                 temp_id
                                 FROM active_trade_owners 
                                 WHERE is_active = true
                                 GROUP BY temp_id
                                 )owner"), function ($join) {
                $join->on("owner.temp_id", "active_trade_licences.id");
            })
            ->where("active_trade_licences.is_active", true)
            ->where("active_trade_licences.user_id", $refUserId)
            ->get();
        // if ($request->ulbId) {
        //     $licence =  $licence->where("active_trade_licences.ulb_id", $request->ulbId);
        // }
        // $expireLicence = ExpireLicence::select(
        //     "expire_licences.id",
        //     "expire_licences.application_no",
        //     "expire_licences.provisional_license_no",
        //     "expire_licences.license_no",
        //     "expire_licences.document_upload_status",
        //     "expire_licences.payment_status",
        //     "expire_licences.pending_status",
        //     "expire_licences.firm_name",
        //     "expire_licences.apply_date",
        //     "expire_licences.apply_from",
        //     "owner.owner_name",
        //     "owner.guardian_name",
        //     "owner.mobile_no",
        //     "owner.email_id",
        //     DB::raw("'expired' as license_type"),
        // )
        //     ->join(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
        //                  STRING_AGG(guardian_name,',') AS guardian_name,
        //                  STRING_AGG(mobile::TEXT,',') AS mobile_no,
        //                  STRING_AGG(emailid,',') AS email_id,
        //                  licence_id
        //                  FROM expire_licence_owners 
        //                  WHERE status =1
        //                  GROUP BY licence_id
        //                  )owner"), function ($join) {
        //         $join->on("owner.licence_id", "expire_licences.id");
        //     })
        //     ->where("expire_licences.status", 1)
        //     ->where("expire_licences.user_id", $refUserId);
        // if ($request->ulbId) {
        //     $expireLicence =  $expireLicence->where("expire_licences.ulb_id", $request->ulbId);
        // }
        // $final = $licence->union($expireLicence)
        //     ->get();
        return responseMsg(true, "", remove_null($licence));
        // } catch (Exception $e) {
        //     return responseMsg(false, $e->getMessage(), "");
        // }
    }

    # Serial No : 28
    public function readCitizenLicenceDtl($request)
    {
        try {
            $id = $request->id;
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refUlbId       = $refUser->ulb_id ?? 0;
            $refWorkflowId  = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
            $tbl = "expire";
            $application = ActiveTradeLicence::select("id")->find($id);
            if ($application) {
                $tbl = "active";
            }
            $init_finish = $this->_parent->iniatorFinisher($refUserId, $refUlbId, $refWorkflowId);
            $finisher = $init_finish['finisher'];
            $finisher['short_user_name'] = Config::get('TradeConstant.USER-TYPE-SHORT-NAME.' . strtoupper($init_finish['finisher']['role_name']));
            $mUserType      = $this->_parent->userType($refWorkflowId);
            $refApplication = $this->_counter->getAllLicenceById($id);
            $mStatus = $this->_counter->applicationStatus($id);
            $mItemName      = "";
            $mCods          = "";
            if ($refApplication->nature_of_bussiness) {
                $items = TradeParamItemType::itemsById($refApplication->nature_of_bussiness);
                foreach ($items as $val) {
                    $mItemName  .= $val->trade_item . ",";
                    $mCods      .= $val->trade_code . ",";
                }
                $mItemName = trim($mItemName, ',');
                $mCods = trim($mCods, ',');
            }
            $refApplication->items      = $mItemName;
            $refApplication->items_code = $mCods;
            $refOwnerDtl                = $this->_counter->getAllOwnereDtlByLId($id);
            $refTransactionDtl          = TradeTransaction::listByLicId($id);
            $refTimeLine                = $this->_counter->getTimelin($id);
            $refUploadDocuments         = $this->_counter->getLicenceDocuments($id, $tbl)->map(function ($val) {
                $val->document_path = !empty(trim($val->document_path)) ? $this->_counter->readDocumentPath($val->document_path) : "";
                return $val;
            });
            $pendingAt  = $init_finish['initiator']['id'];
            $mlevelData = $this->_counter->getWorkflowTrack($id);
            if ($mlevelData) {
                $pendingAt = $mlevelData->receiver_user_type_id;
            }
            $mworkflowRoles = $this->_parent->getWorkFlowAllRoles($refUserId, $refUlbId, $refWorkflowId, true);
            $mileSton = $this->_parent->sortsWorkflowRols($mworkflowRoles);

            $data['licenceDtl']     = $refApplication;
            $data['ownerDtl']       = $refOwnerDtl;
            $data['transactionDtl'] = $refTransactionDtl;
            $data['pendingStatus']  = $mStatus;
            $data['remarks']        = $refTimeLine;
            $data['documents']      = $refUploadDocuments;
            $data["userType"]       = $mUserType;
            $data["roles"]          = $mileSton;
            $data["pendingAt"]      = $pendingAt;
            $data["levelData"]      = $mlevelData;
            $data['finisher']       = $finisher;
            $data = remove_null($data);
            return responseMsg(true, "", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), '');
        }
    }
}
