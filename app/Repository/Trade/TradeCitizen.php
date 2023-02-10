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
use App\Models\Trade\RejectedTradeLicence;
use App\Models\Trade\TradeFineRebete;
use App\Models\Trade\TradeLicence;
use App\Models\Trade\TradeRenewal;

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
                $refOldLicece = ActiveTradeLicence::find($mOldLicenceId);
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
                ->where("temp_id", $args["id"])
                ->where("status", 2)
                ->first();
            if (!$RazorPayRequest) {
                throw new Exception("Data Not Found");
            }
            $refLecenceData = ActiveTradeLicence::find($args["id"]);
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
                ->where("id", $refLecenceData->ward_id)
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
            $RazorPayResponse->temp_id   = $RazorPayRequest->temp_id;
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
            $Tradetransaction->ward_id          = $refLecenceData->ward_id;
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
            $Tradetransaction->tran_no   = $args["transactionNo"]; //$this->createTransactionNo($transaction_id);//"TRANML" . date('d') . $transaction_id . date('Y') . date('m') . date('s');
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
        try {

            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $refWorkflowId      = Config::get('workflow-constants.TRADE_WORKFLOW_ID');

            $ActiveLicence = ActiveTradeLicence::select(
                "active_trade_licences.id",
                "active_trade_licences.application_no",
                "active_trade_licences.provisional_license_no",
                "active_trade_licences.license_no",
                "active_trade_licences.license_date",
                "active_trade_licences.valid_from",
                "active_trade_licences.valid_upto",
                "active_trade_licences.document_upload_status",
                "active_trade_licences.payment_status",
                "active_trade_licences.pending_status",
                "active_trade_licences.firm_name",
                "active_trade_licences.application_date",
                "active_trade_licences.apply_from",
                "active_trade_licences.application_type_id",
                "active_trade_licences.ulb_id",
                "owner.owner_name",
                "owner.guardian_name",
                "owner.mobile_no",
                "owner.email_id",
                "ulb_masters.ulb_name",
                DB::raw("'active' as license_type"),
            )
                ->join("ulb_masters","ulb_masters.id","active_trade_licences.ulb_id")
                ->leftjoin(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                    STRING_AGG(guardian_name,',') AS guardian_name,
                                    STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                    STRING_AGG(email_id,',') AS email_id,
                                    active_trade_owners.temp_id
                                    FROM active_trade_owners 
                                    JOIN active_trade_licences on active_trade_licences.citizen_id = $refUserId 
                                        AND active_trade_licences.id = active_trade_owners.temp_id 
                                    WHERE active_trade_owners.is_active = true
                                    GROUP BY active_trade_owners.temp_id
                                    )owner"), function ($join) {
                    $join->on("owner.temp_id", "active_trade_licences.id");
                })
                ->where("active_trade_licences.is_active", true)
                ->where("active_trade_licences.citizen_id", $refUserId);
                // ->get();
            $RejectedLicence = RejectedTradeLicence::select(
                "rejected_trade_licences.id",
                "rejected_trade_licences.application_no",
                "rejected_trade_licences.provisional_license_no",
                "rejected_trade_licences.license_no",
                "rejected_trade_licences.license_date",
                "rejected_trade_licences.valid_from",
                "rejected_trade_licences.valid_upto",
                "rejected_trade_licences.document_upload_status",
                "rejected_trade_licences.payment_status",
                "rejected_trade_licences.pending_status",
                "rejected_trade_licences.firm_name",
                "rejected_trade_licences.application_date",
                "rejected_trade_licences.apply_from",
                "rejected_trade_licences.application_type_id",
                "rejected_trade_licences.ulb_id",
                "owner.owner_name",
                "owner.guardian_name",
                "owner.mobile_no",
                "owner.email_id",
                "ulb_masters.ulb_name",
                DB::raw("'rejected' as license_type"),
            )
                ->join("ulb_masters","ulb_masters.id","rejected_trade_licences.ulb_id")
                ->leftjoin(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                    STRING_AGG(guardian_name,',') AS guardian_name,
                                    STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                    STRING_AGG(email_id,',') AS email_id,
                                    rejected_trade_owners.temp_id
                                    FROM rejected_trade_owners
                                    JOIN rejected_trade_licences on rejected_trade_licences.citizen_id = $refUserId 
                                        AND rejected_trade_licences.id = rejected_trade_owners.temp_id 
                                    WHERE rejected_trade_owners.is_active = true
                                    GROUP BY rejected_trade_owners.temp_id
                                    )owner"), function ($join) {
                    $join->on("owner.temp_id", "rejected_trade_licences.id");
                })
                ->where("rejected_trade_licences.is_active", true)
                ->where("rejected_trade_licences.citizen_id", $refUserId);
                // ->get();

            $ApprovedLicence = TradeLicence::select(
                "trade_licences.id",
                "trade_licences.application_no",
                "trade_licences.provisional_license_no",
                "trade_licences.license_no",
                "trade_licences.license_date",
                "trade_licences.valid_from",
                "trade_licences.valid_upto",
                "trade_licences.document_upload_status",
                "trade_licences.payment_status",
                "trade_licences.pending_status",
                "trade_licences.firm_name",
                "trade_licences.application_date",
                "trade_licences.apply_from",
                "trade_licences.application_type_id",
                "trade_licences.ulb_id",
                "owner.owner_name",
                "owner.guardian_name",
                "owner.mobile_no",
                "owner.email_id",
                "ulb_masters.ulb_name",
                DB::raw("'approved' as license_type"),
            )
                ->join("ulb_masters","ulb_masters.id","trade_licences.ulb_id")
                ->leftjoin(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                        STRING_AGG(guardian_name,',') AS guardian_name,
                                        STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                        STRING_AGG(email_id,',') AS email_id,
                                        trade_owners.temp_id
                                        FROM trade_owners
                                        JOIN trade_licences on trade_licences.citizen_id = $refUserId 
                                        AND trade_licences.id = trade_owners.temp_id 
                                        WHERE trade_owners.is_active = true
                                        GROUP BY trade_owners.temp_id
                                        )owner"), function ($join) {
                    $join->on("owner.temp_id", "trade_licences.id");
                })
                ->where("trade_licences.is_active", true)
                ->where("trade_licences.citizen_id", $refUserId);
                // ->get();
            $OldLicence = TradeRenewal::select(
                "trade_renewals.id",
                "trade_renewals.application_no",
                "trade_renewals.provisional_license_no",
                "trade_renewals.license_no",
                "trade_renewals.license_date",
                "trade_renewals.valid_from",
                "trade_renewals.valid_upto",
                "trade_renewals.document_upload_status",
                "trade_renewals.payment_status",
                "trade_renewals.pending_status",
                "trade_renewals.firm_name",
                "trade_renewals.application_date",
                "trade_renewals.apply_from",
                "trade_renewals.application_type_id",
                "trade_renewals.ulb_id",
                "owner.owner_name",
                "owner.guardian_name",
                "owner.mobile_no",
                "owner.email_id",
                "ulb_masters.ulb_name",
                DB::raw("'old' as license_type"),
            )
                ->join("ulb_masters","ulb_masters.id","trade_renewals.ulb_id")
                ->leftjoin(DB::raw("(select STRING_AGG(owner_name,',') AS owner_name,
                                        STRING_AGG(guardian_name,',') AS guardian_name,
                                        STRING_AGG(mobile_no::TEXT,',') AS mobile_no,
                                        STRING_AGG(email_id,',') AS email_id,
                                        trade_owners.temp_id
                                        FROM trade_owners
                                        JOIN trade_renewals on trade_renewals.citizen_id = $refUserId 
                                        AND trade_renewals.id = trade_owners.temp_id 
                                        WHERE trade_owners.is_active = true
                                        GROUP BY trade_owners.temp_id
                                        )owner"), function ($join) {
                    $join->on("owner.temp_id", "trade_renewals.id");
                })
                ->where("trade_renewals.is_active", true)
                ->where("trade_renewals.citizen_id", $refUserId);
        
            $final = $ActiveLicence->union($RejectedLicence)
                    ->union($ApprovedLicence)->union($OldLicence)
                    ->get();
            $final1 = $final->map(function($val){
                $val->option = [];
                $nextMonth = Carbon::now()->addMonths(1)->format('Y-m-d');
                if(trim($val->license_type)=="approved" && $val->valid_upto < $nextMonth)
                {
                    $val->option=["RENEWAL","AMENDMENT"];
                }
                if(trim($val->license_type)=="approved" && $val->valid_upto >= Carbon::now()->format('Y-m-d'))
                {
                    $val->option=["RENEWAL","AMENDMENT","SURRENDER"];
                }
                return $val;
            });
            return responseMsg(true, "", remove_null($final));
        } 
        catch (Exception $e) 
        {
            return responseMsg(false, $e->getMessage(), "");
        }
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
