<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\ReqPayment;
use App\Http\Requests\Water\ReqWaterPayment;
use App\Http\Requests\Water\siteAdjustment;
use App\MicroServices\IdGeneration;
use App\Models\Payment\TempTransaction;
use App\Models\Payment\WebhookPaymentData;
use App\Models\Property\PropTransaction;
use App\Models\Water\WaterApplication;
use App\Models\Water\WaterChequeDtl;
use App\Models\Water\WaterConnectionCharge;
use App\Models\Water\WaterConnectionThroughMstr;
use App\Models\Water\WaterConnectionTypeMstr;
use App\Models\Water\WaterConsumer;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterOwnerTypeMstr;
use App\Models\Water\WaterParamPipelineType;
use App\Models\Water\WaterPenaltyInstallment;
use App\Models\Water\WaterPropertyTypeMstr;
use App\Models\Water\WaterRazorPayRequest;
use App\Models\Water\WaterSiteInspection;
use App\Models\Water\WaterTran;
use App\Models\Water\WaterTranDetail;
use App\Models\Workflows\WfRoleusermap;
use App\Repository\Water\Concrete\WaterNewConnection;
use App\Traits\Ward;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * | ----------------------------------------------------------------------------------
 * | Water Module |
 * |-----------------------------------------------------------------------------------
 * | Created On-10-02-2023
 * | Created By-Sam kerketta 
 * | Created For-Water related Transaction and Payment Related operations
 */

class WaterPaymentController extends Controller
{
    use Ward;
    use Workflow;

    // water Constant
    private $_waterRoles;
    private $_waterMasterData;

    public function __construct()
    {
        $this->_waterRoles = Config::get('waterConstaint.ROLE-LABEL');
        $this->_waterMasterData = Config::get('waterConstaint.WATER_MASTER_DATA');
    }


    /**
     * | Get The Master Data Related to Water 
     * | Fetch all master Data At Once
     * | @var 
     * | @return returnValues
        | Serial No : 00 
     */
    public function getWaterMasterData()
    {
        try {
            $redisConn = Redis::connection();
            $returnValues = [];
            $mWaterParamPipelineType = new WaterParamPipelineType();
            $mWaterConnectionTypeMstr = new WaterConnectionTypeMstr();
            $mWaterConnectionThroughMstr = new WaterConnectionThroughMstr();
            $mWaterPropertyTypeMstr = new WaterPropertyTypeMstr();
            $mWaterOwnerTypeMstr = new WaterOwnerTypeMstr();

            $waterParamPipelineType = json_decode(Redis::get('water-param-pipeline-type'));
            $waterConnectionTypeMstr = json_decode(Redis::get('water-connection-type-mstr'));
            $waterConnectionThroughMstr = json_decode(Redis::get('water-connection-through-mstr'));
            $waterPropertyTypeMstr = json_decode(Redis::get('water-property-type-mstr'));
            $waterOwnerTypeMstr = json_decode(Redis::get('water-owner-type-mstr'));

            // Ward Masters
            if (!$waterParamPipelineType) {
                $waterParamPipelineType = $mWaterParamPipelineType->getWaterParamPipelineType();            // Get PipelineType By Model Function
                $redisConn->set('water-param-pipeline-type', json_encode($waterParamPipelineType));                  // Caching
            }

            if (!$waterConnectionTypeMstr) {
                $waterConnectionTypeMstr = $mWaterConnectionTypeMstr->getWaterConnectionTypeMstr();            // Get PipelineType By Model Function
                $redisConn->set('water-connection-type-mstr', json_encode($waterConnectionTypeMstr));                  // Caching
            }

            if (!$waterConnectionThroughMstr) {
                $waterConnectionThroughMstr = $mWaterConnectionThroughMstr->getWaterConnectionThroughMstr();            // Get PipelineType By Model Function
                $redisConn->set('water-connection-through-mstr', json_encode($waterConnectionThroughMstr));                  // Caching
            }

            if (!$waterPropertyTypeMstr) {
                $waterPropertyTypeMstr = $mWaterPropertyTypeMstr->getWaterPropertyTypeMstr();            // Get PipelineType By Model Function
                $redisConn->set('water-property-type-mstr', json_encode($waterPropertyTypeMstr));                  // Caching
            }

            if (!$waterOwnerTypeMstr) {
                $waterOwnerTypeMstr = $mWaterOwnerTypeMstr->getWaterOwnerTypeMstr();            // Get PipelineType By Model Function
                $redisConn->set('water-owner-type-mstr', json_encode($waterOwnerTypeMstr));                  // Caching
            }

            $masterValues = [
                'water_param_pipeline_type'     => $waterParamPipelineType,
                'water_connection_type_mstr'    => $waterConnectionTypeMstr,
                'water_connection_through_mstr' => $waterConnectionThroughMstr,
                'water_property_type_mstr'      => $waterPropertyTypeMstr,
                'water_owner_type_mstr'         => $waterOwnerTypeMstr,
            ];

            # Config Master Data 
            $refMasterData = $this->_waterMasterData;
            $confugMasterValues = [
                "pipeline_size_type"    => $refMasterData['PIPELINE_SIZE_TYPE'],
                "pipe_diameter"         => $refMasterData['PIPE_DIAMETER'],
                "pipe_quality"          => $refMasterData['PIPE_QUALITY'],
                "road_type"             => $refMasterData['ROAD_TYPE'],
                "ferule_size"           => $refMasterData['FERULE_SIZE']
            ];
            $returnValues = collect($masterValues)->merge($confugMasterValues);
            return responseMsgs(true, "list of Water Master Data!", remove_null($returnValues), "", "01", "ms", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
        }
    }


    /**
     * | Get Consumer Payment History 
     * | Collect All the transaction relate to the respective Consumer 
     * | @param request
     * | @var mWaterTran
     * | @var mWaterConsumer
     * | @var mWaterConsumerDemand
     * | @var mWaterTranDetail
     * | @var transactions
     * | @var waterDtls
     * | @var waterTrans
     * | @var applicationId
     * | @var connectionTran
     * | @return transactions  Consumer / Connection Data 
        | Serial No : 01
        | Working
     */
    public function getConsumerPaymentHistory(Request $request)
    {
        $request->validate([
            'consumerId' => 'required|digits_between:1,9223372036854775807'
        ]);
        try {
            $mWaterTran = new WaterTran();
            $mWaterConsumer = new WaterConsumer();
            $mWaterConsumerDemand = new WaterConsumerDemand();
            $mWaterTranDetail = new WaterTranDetail();

            $transactions = array();

            # consumer Details
            $waterDtls = $mWaterConsumer->getConsumerDetailById($request->consumerId);
            if (!$waterDtls)
                throw new Exception("Water Consumer Not Found!");

            # if Consumer in made vie application
            $applicationId = $waterDtls->apply_connection_id;
            if (!$applicationId)
                throw new Exception("This Consumer has not ApplicationId!!");

            # if demand transaction exist
            $connectionTran[] = $mWaterTran->getTransNo($applicationId, null)->first();                        // Water Connection payment History
            if (!$connectionTran)
                throw new Exception("Water Application Tran Details not Found!!");

            $waterTrans = $mWaterTran->ConsumerTransaction($request->consumerId)->get();         // Water Consumer Payment History
            $waterTrans = collect($waterTrans)->map(function ($value, $key) use ($mWaterConsumerDemand, $mWaterTranDetail) {
                $demandId = $mWaterTranDetail->getDetailByTranId($value['id']);
                $value['demand'] = $mWaterConsumerDemand->getDemandBydemandId($demandId['demand_id']);
                return $value;
            });

            $transactions['Consumer'] = collect($waterTrans)->sortByDesc('id')->values();
            $transactions['connection'] = collect($connectionTran)->sortByDesc('id');

            return responseMsgs(true, "", remove_null($transactions), "", "01", "ms", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
        }
    }


    /**
     * | Generate the payment Recipt Using transactionId
     * | @param req  transactionId
     * | @var mPaymentData 
     * | @var mWaterApplication
     * | @var mWaterTransaction
     * | @var mTowards
     * | @var mAccDescription
     * | @var mDepartmentSection
     * | @var applicationDtls
     * | @var applicationId
     * | @var applicationDetails
     * | @var webhookData
     * | @var webhookDetails
     * | @var transactionDetails
     * | @var waterTrans
     * | @var epoch
     * | @var dateTime
     * | @var transactionTime
     * | @var responseData
     * | @return responseData  Data for the payment Recipt
        | Serial No : 02
        | Recheck 
        | Search From Water trans table Not in webhook table 
        | may not used
     */
    public function generatePaymentReceipt(Request $req)
    {
        $req->validate([
            'transactionNo' => 'required'
        ]);

        try {
            $mPaymentData = new WebhookPaymentData();
            $mWaterApplication = new WaterApplication();
            $mWaterTransaction = new WaterTran();

            $mTowards = Config::get('waterConstaint.TOWARDS');
            $mAccDescription = Config::get('waterConstaint.ACCOUNT_DESCRIPTION');
            $mDepartmentSection = Config::get('waterConstaint.DEPARTMENT_SECTION');

            $applicationDtls = $mPaymentData->getApplicationId($req->transactionNo);
            $applicationId = json_decode($applicationDtls)->applicationId;

            $applicationDetails = $mWaterApplication->getWaterApplicationsDetails($applicationId);
            $webhookData = $mPaymentData->getPaymentDetailsByPId($req->transactionNo);
            $webhookDetails = collect($webhookData)->last();

            $transactionDetails = $mWaterTransaction->getTransactionDetailsById($applicationId);
            $waterTrans = collect($transactionDetails)->last();

            $epoch = $webhookDetails->payment_created_at;
            $dateTime = new DateTime("@$epoch");
            $transactionTime = $dateTime->format('H:i:s');

            $responseData = [
                "departmentSection" => $mDepartmentSection,
                "accountDescription" => $mAccDescription,
                "transactionDate" => $waterTrans->tran_date,
                "transactionNo" => $waterTrans->tran_no,
                "transactionTime" => $transactionTime,
                "applicationNo" => $applicationDetails->application_no,
                "customerName" => $applicationDetails->applicant_name,
                "customerMobile" => $applicationDetails->mobile_no,
                "address" => $applicationDetails->address,
                "paidFrom" => "",
                "paidFromQtr" => "",
                "paidUpto" => "",
                "paidUptoQtr" => "",
                "paymentMode" => $waterTrans->payment_mode,
                "bankName" => $webhookDetails->payment_bank ?? null,
                "branchName" => "",
                "chequeNo" => "",
                "chequeDate" => "",
                "noOfFlats" => "",
                "monthlyRate" => "",
                "demandAmount" => "",  // if the trans is diff
                "taxDetails" => "",
                "ulbId" => $webhookDetails->ulb_id,
                "WardNo" => $applicationDetails->ward_id,
                "towards" => $mTowards,
                "description" => $waterTrans->tran_type,
                "totalPaidAmount" => $webhookDetails->payment_amount,
                "paidAmtInWords" => getIndianCurrency($webhookDetails->payment_amount),
            ];
            return responseMsgs(true, "Payment Receipt", remove_null($responseData), "", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "", "", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }


    /**
     * | Generate the payment Receipt for Demand / In Bulk amd Indipendent
     * | @param request
     * | @var 
     * | @return 
        | Serial No : 03
        | Recheck
     */
    public function generateOfflinePaymentReceipt(Request $req)
    {
        $req->validate([
            'transactionNo' => 'required'
        ]);
        try {
            $refTransactionNo = $req->transactionNo;
            $mWaterConnectionCharge = new WaterConnectionCharge();
            $mWaterPenaltyInstallment = new WaterPenaltyInstallment();
            $mWaterApplication = new WaterApplication();
            $mWaterChequeDtl = new WaterChequeDtl();
            $mWaterTran = new WaterTran();

            $mTowards = Config::get('waterConstaint.TOWARDS');
            $mAccDescription = Config::get('waterConstaint.ACCOUNT_DESCRIPTION');
            $mDepartmentSection = Config::get('waterConstaint.DEPARTMENT_SECTION');
            $mPaymentModes = Config::get('payment-constants.PAYMENT_OFFLINE_MODE');

            # transaction Deatils
            $transactionDetails = $mWaterTran->getTransactionByTransactionNo($refTransactionNo)
                ->firstOrFail();

            #  Data not equal to Cash
            if (!in_array($transactionDetails['payment_mode'], [$mPaymentModes['1'], $mPaymentModes['5']])) {
                $chequeDetails = $mWaterChequeDtl->getChequeDtlsByTransId($transactionDetails['id'])->first();
            }
            # Application Deatils
            $applicationDetails = $mWaterApplication->getDetailsByApplicationId($transactionDetails->related_id)->firstOrFail();

            # Connection Charges
            $connectionCharges = $mWaterConnectionCharge->getChargesById($transactionDetails->demand_id)
                ->firstOrFail();

            # if penalty Charges
            $individulePenaltyCharges = $mWaterPenaltyInstallment->getPenaltyByApplicationId($transactionDetails->related_id)
                ->where('paid_status', 1)
                ->get();
            if ($individulePenaltyCharges) {
                $totalPenaltyAmount = collect($individulePenaltyCharges)->map(function ($value) {
                    return $value['balance_amount'];
                })->sum();
            }

            # Transaction Date
            $refDate = $transactionDetails->tran_date;
            $transactionDate = Carbon::parse($refDate)->format('Y-m-d');

            $returnValues = [
                "departmentSection" => $mDepartmentSection,
                "accountDescription" => $mAccDescription,
                "transactionDate" => $transactionDate,
                "transactionNo" => $refTransactionNo,
                // "transactionTime" => $transactionTime,
                "applicationNo" => $applicationDetails['application_no'],
                "customerName" => $applicationDetails['applicantname'],
                "customerMobile" => $applicationDetails['mobileno'],
                "address" => $applicationDetails['address'],
                "paidFrom" => $connectionCharges['charge_category'],
                "paidFromQtr" => "",
                "paidUpto" => "",
                "paidUptoQtr" => "",
                "paymentMode" => $transactionDetails['payment_mode'],
                "bankName" => $chequeDetails[''] ?? null,                                   // in case of cheque,dd,nfts
                "branchName" => $chequeDetails[''] ?? null,                                 // in case of chque,dd,nfts
                "chequeNo" => $chequeDetails['']  ?? null,                                   // in case of chque,dd,nfts
                "chequeDate" => $chequeDetails[''] ?? null,                                 // in case of chque,dd,nfts
                "monthlyRate" => "",
                "demandAmount" => $transactionDetails->amount,
                "taxDetails" => "",
                "ulbId" => $transactionDetails['ulb_id'],
                "ulbName" => $applicationDetails['ulb_name'],
                "WardNo" => $applicationDetails['ward_name'],
                "towards" => $mTowards,
                "description" => $mAccDescription,
                "totalPaidAmount" => $transactionDetails->amount,
                "penaltyAmount" => $totalPenaltyAmount,
                "paidAmtInWords" => getIndianCurrency($transactionDetails->amount),
            ];
            return responseMsgs(true, "Payment Receipt", remove_null($returnValues), "", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
        }
    }


    /**
     * | Site Inspection Details Entry
     * | Save the adjusted Data
     * | @param request
     * | @var mWaterSiteInspection
     * | @var mWaterNewConnection
     * | @var mWaterConnectionCharge
     * | @var connectionCatagory
     * | @var waterDetails
     * | @var applicationCharge
     * | @var oldChargeAmount
     * | @var newConnectionCharges
     * | @var installment
     * | @var waterFeeId
     * | @var newChargeAmount
     * | @var 
     * | @return
        | Serial No : 04
        | Working
        | Change the Adjustment
     */
    public function saveSitedetails(siteAdjustment $request)
    {
        try {
            $mWaterSiteInspection = new WaterSiteInspection();
            $mWaterNewConnection = new WaterNewConnection();
            $mWaterConnectionCharge = new WaterConnectionCharge();

            $connectionCatagory = Config::get('waterConstaint.CHARGE_CATAGORY');
            $waterDetails = WaterApplication::find($request->applicationId);

            # Check Related Condition
            $this->CheckInspectionCondition($request, $waterDetails);

            # Get the Applied Connection Charge
            $applicationCharge = $mWaterConnectionCharge->getWaterchargesById($request->applicationId)
                ->where('charge_category', '!=', $connectionCatagory['SITE_INSPECTON'])
                ->firstOrFail();
            $oldChargeAmount = $applicationCharge['amount'];

            DB::beginTransaction();
            # Generating Demand for new InspectionData
            return $newConnectionCharges = objToArray($mWaterNewConnection->calWaterConCharge($request));
            if (!$newConnectionCharges['status']) {
                throw new Exception(
                    $newConnectionCharges['errors']
                );
            }
            # param for the new Charges
            $installment = $newConnectionCharges['installment_amount'];
            $waterFeeId = $newConnectionCharges['water_fee_mstr_id'];
            $newChargeAmount = $newConnectionCharges['conn_fee_charge']['amount'];

            # If the Adjustment Hamper
            if ($oldChargeAmount != $newChargeAmount) {
                $this->adjustmentInConnection($request, $newConnectionCharges, $installment, $waterDetails);
            }

            $mWaterSiteInspection->storeInspectionDetails($request, $waterFeeId, $waterDetails);
            DB::commit();
            return responseMsgs(true, "Site Inspection Done!", $request->applicationId, "", "01", "ms", "POST", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
        }
    }


    /**
     * | Check the Inspection related Details
     * | Check Conditions
     * | @param request
     * | @var waterDetails
     * | @var mWfRoleUsermap
     * | @var waterRoles
     * | @var userId
     * | @var workflowId
     * | @var getRoleReq
     * | @var readRoleDtls
     * | @var roleId
        | Serial No : 04.01
        | Working
     */
    public function CheckInspectionCondition($request, $waterDetails)
    {
        $mWfRoleUsermap = new WfRoleusermap();
        $waterRoles = $this->_waterRoles;

        # check the login user is Eo or not
        $userId = authUser()->id;
        $workflowId = $waterDetails->workflow_id;
        $getRoleReq = new Request([                                                 # make request to get role id of the user
            'userId' => $userId,
            'workflowId' => $workflowId
        ]);
        $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
        $roleId = $readRoleDtls->wf_role_id;

        # Checking Condition
        if ($roleId != $waterRoles['JE']) {
            throw new Exception("You are not Junier Enginer!");
        }
        if ($waterDetails->current_role != $waterRoles['JE']) {
            throw new Exception("Application Is Not under Junier Injiner!");
        }
        if ($waterDetails->is_field_verified == true) {
            throw new Exception("Application's site is Already Approved!");
        }
    }


    /**
     * | Changes in the Site Inspection Adjustment
     * | Updating the Connection Charges And the Related Deatils
     * | @param request
     * | @param newConnectionCharges
     * | @param installment
        | Serial No : 04.02
        | Working
     */
    public function adjustmentInConnection($request, $newConnectionCharges, $installment, $waterApplicationDetails)
    {
        $applicationId = $request->applicationId;
        $newCharge = $newConnectionCharges['conn_fee_charge']['amount'];
        $mWaterPenaltyInstallment = new WaterPenaltyInstallment();
        $mWaterConnectionCharge = new WaterConnectionCharge();
        $mWaterApplication = new WaterApplication();
        $mWaterTran = new WaterTran();
        $chargeCatagory = Config::get('waterConstaint.CHARGE_CATAGORY');

        # in case of connection charge is 0
        if ($newCharge == 0) {
            $mWaterTran->saveZeroConnectionCharg($newCharge, $waterApplicationDetails->ulb_id, $request, $applicationId, $connectionId);
        }
        # get Water Application Details
        $mWaterApplication->updatePaymentStatus($applicationId, false);                     // Update the payment status false         

        # water penalty
        if (!is_null($installment)) {
            foreach ($installment as $installments) {
                $mWaterPenaltyInstallment->saveWaterPenelty($applicationId, $installments);
            }
        }
        # connection charges
        $request->merge([
            'chargeCatagory' => $chargeCatagory['SITE_INSPECTON'],
            'ward_id' => $waterApplicationDetails['ward_id']
        ]);
        $newConnectionCharges['conn_fee_charge']['amount'] =
            $connectionId = $mWaterConnectionCharge->saveWaterCharge($applicationId, $request, $newConnectionCharges);
    }


    /**
     * | Iniciate demand payment / In Case Of Online
     * | Online Payment Of Consumer Demand
     * | @param request
     * | @var 
     * | @return 
        | Serial No : 05
        | Recheck / Not Working
     */
    public function initiateOnlineDemandPayment(Request $request)
    {
        try {
            $request->validate([
                'id'                => 'required|digits_between:1,9223372036854775807',
                'applycationType'   => 'required|string|in:connection,consumer',
            ]);

            $refUser            = Auth()->user();
            $refUserId          = $refUser->id;
            $refUlbId           = $refUser->ulb_id;

            #------------ new connection --------------------
            DB::beginTransaction();
            if ($request->applycationType == "consumer") {
                $application = WaterApplication::find($request->id);
                if (!$application) {
                    throw new Exception("Data Not Found!......");
                }
                $cahges = $this->getWaterConnectionChages($application->id);
                if (!$cahges) {
                    throw new Exception("No Anny Due Amount!......");
                }
                $myRequest = new \Illuminate\Http\Request();
                $myRequest->setMethod('POST');
                $myRequest->request->add(['amount' => $cahges->amount]);
                $myRequest->request->add(['workflowId' => $application->workflow_id]);
                $myRequest->request->add(['id' => $application->id]);
                $myRequest->request->add(['departmentId' => 2]);
                $temp = $this->saveGenerateOrderid($myRequest);
                $RazorPayRequest = new WaterRazorPayRequest;
                $RazorPayRequest->related_id   = $application->id;
                $RazorPayRequest->payment_from = "New Connection";
                $RazorPayRequest->amount       = $cahges->amount;
                $RazorPayRequest->demand_from_upto = $cahges->ids;
                $RazorPayRequest->ip_address   = $request->ip();
                $RazorPayRequest->order_id        = $temp["orderId"];
                $RazorPayRequest->department_id = $temp["departmentId"];
                $RazorPayRequest->save();
            }
            #--------------------water Consumer----------------------
            else {
            }
            DB::commit();
            $temp['name']       = $refUser->user_name;
            $temp['mobile']     = $refUser->mobile;
            $temp['email']      = $refUser->email;
            $temp['userId']     = $refUser->id;
            $temp['ulbId']      = $refUser->ulb_id;
            $temp["applycationType"] = $request->applycationType;
            return responseMsg(true, "", $temp);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $request->all());
        }
    }


    /**
     * | Online Payment for the consumer Demand
     * | Data After the Webhook Payment / Called by the Webhook
     * | @param
        | Serial No : 06
        | Recheck / Not Working
     */
    public function endOnlineDemandPayment($args)
    {
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id ?? $args["userId"];
            $refUlbId       = $refUser->ulb_id ?? $args["ulbId"];
            $mNowDate       = Carbon::now()->format('Y-m-d');
            $mTimstamp      = Carbon::now()->format('Y-m-d H:i:s');
            $cahges         = null;
            $chargeData     = (array)null;
            $application    = null;
            $mDemands       = (array)null;

            #-----------valication------------------- 
            $RazorPayRequest = WaterRazorPayRequest::select("*")
                ->where("order_id", $args["orderId"])
                ->where("related_id", $args["id"])
                ->where("status", 2)
                ->first();
            if (!$RazorPayRequest) {
                throw new Exception("Data Not Found");
            }
            if ($RazorPayRequest->payment_from == "New Connection") {
                $application = WaterApplication::find($args["id"]);
                $cahges = 0;
                $id = explode(",", $RazorPayRequest->demand_from_upto);
                if ($id) {
                    $mDemands = WaterConnectionCharge::select("*")
                        ->whereIn("id", $id)
                        ->get();
                    $cahges = ($mDemands->sum("amount"));
                }
                $chargeData["total_charge"] = $cahges;
            } elseif ($RazorPayRequest->payment_from == "Demand Collection") {
                $application = null;
            }
            if (!$application) {
                throw new Exception("Application Not Found!......");
            }
            $applicationId = $args["id"];
            #-----------End valication----------------------------

            #-------------Calculation----------------------------- 
            if (!$chargeData || round($args['amount']) != round($chargeData['total_charge'])) {
                throw new Exception("Payble Amount Missmatch!!!");
            }

            $transactionType = $RazorPayRequest->payment_from;

            $totalCharge = $chargeData['total_charge'];
            #-------------End Calculation-----------------------------
            #-------- Transection -------------------
            DB::beginTransaction();

            $RazorPayResponse = new WaterRazorPayResponse;
            $RazorPayResponse->related_id   = $RazorPayRequest->related_id;
            $RazorPayResponse->request_id   = $RazorPayRequest->id;
            $RazorPayResponse->amount       = $args['amount'];
            $RazorPayResponse->merchant_id  = $args['merchantId'] ?? null;
            $RazorPayResponse->order_id     = $args["orderId"];
            $RazorPayResponse->payment_id   = $args["paymentId"];
            $RazorPayResponse->save();

            $RazorPayRequest->status = 1;
            $RazorPayRequest->update();

            $Tradetransaction = new WaterTran;
            $Tradetransaction->related_id       = $applicationId;
            $Tradetransaction->ward_id          = $application->ward_id;
            $Tradetransaction->tran_type        = $transactionType;
            $Tradetransaction->tran_date        = $mNowDate;
            $Tradetransaction->payment_mode     = "Online";
            $Tradetransaction->amount           = $totalCharge;
            $Tradetransaction->emp_dtl_id       = $refUserId;
            $Tradetransaction->created_at       = $mTimstamp;
            $Tradetransaction->ip_address       = '';
            $Tradetransaction->ulb_id           = $refUlbId;
            $Tradetransaction->save();
            $transaction_id                     = $Tradetransaction->id;
            $Tradetransaction->tran_no          = $args["transactionNo"];
            $Tradetransaction->update();

            foreach ($mDemands as $val) {
                $TradeDtl = new WaterTranDetail;
                $TradeDtl->tran_id        = $transaction_id;
                $TradeDtl->demand_id      = $val->id;
                $TradeDtl->total_demand   = $val->amount;
                $TradeDtl->application_id   = $val->application_id;
                $TradeDtl->created_at     = $mTimstamp;
                $TradeDtl->save();

                $val->paid_status = true;
                $val->update();
            }

            $application->payment_status = true;
            $application->update();
            ////////////////////////////////////////
            # Check 
            WaterApplication::where('id', $applicationId)
                ->update([
                    'current_role' => $this->_dealingAssistent
                ]);
            /////////////////////////////////////////
            DB::commit();
            #----------End transaction------------------------
            #----------Response------------------------------
            $res['transactionId'] = $transaction_id;
            $res['paymentRecipt'] = config('app.url') . "/api/water/paymentRecipt/" . $applicationId . "/" . $transaction_id;
            return responseMsg(true, "", $res);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $args);
        }
    }


    /**
     * | Consumer Demand Payment 
     * | Offline Payment for the Monthely Payment
     * | @param req
     * | @var offlinePaymentModes
     * | @var todayDate
     * | @var mWaterApplication
     * | @var idGeneration
     * | @var waterTran
     * | @var userId
     * | @var refWaterApplication
     * | @var tranNo
     * | @var charges
     * | @var wardId
     * | @var waterTrans
        | Serial No : 07
        | Working
     */
    public function offlineConnectionPayment(ReqWaterPayment $req)
    {
        try {
            # Variable Assignments
            $offlinePaymentModes = Config::get('payment-constants.PAYMENT_MODE_OFFLINE');
            $todayDate = Carbon::now();
            $mWaterApplication = new WaterApplication();
            $mWaterConnectionCharge = new WaterConnectionCharge();
            $idGeneration = new IdGeneration;
            $waterTran = new WaterTran();
            $userId = auth()->user()->id;                                               # Authenticated user or Ghost User
            $refWaterApplication = $mWaterApplication->getApplicationById($req->applicationId)
                ->firstOrFail();

            $this->verifyPaymentRules($req, $refWaterApplication);

            # Derivative Assignments
            $tranNo = $idGeneration->generateTransactionNo();
            $charges = $mWaterConnectionCharge->getWaterchargesById($req->applicationId)->get();   # get water User connectin charges

            if (!$charges || collect($charges)->isEmpty())
                throw new Exception("Connection Not Available for Payment!");
            # Water Transactions
            $req->merge([
                'userId'    => $userId,
                'todayDate' => $todayDate->format('Y-m-d'),
                'tranNo'    => $tranNo,
                'id'        => $req->applicationId,
                'ulbId'     => authUser()->ulb_id,
            ]);
            DB::beginTransaction();
            $wardId['ward_mstr_id'] = $refWaterApplication['ward_id'];
            $waterTrans = $waterTran->waterTransaction($req, $wardId);

            if (in_array($req['paymentMode'], $offlinePaymentModes)) {
                $req->merge([
                    'chequeDate' => $req['chequeDate'],
                    'tranId' => $waterTrans['id'],
                    'id' => $req->applicationId,
                    'applicationNo' => $refWaterApplication['application_no']
                ]);
                $this->postOtherPaymentModes($req);
            }

            // Reflect on Prop Tran Details
            foreach ($charges as $charges) {
                $charges->paid_status = 1;           // <-------- Update Demand Paid Status 
                $charges->save();

                $waterTranDetail = new WaterTranDetail();
                $waterTranDetail->tran_id           = $waterTrans['id'];
                $waterTranDetail->demand_id         = $charges['id'];
                $waterTranDetail->total_demand      = $charges['balance_amount'];
                $waterTranDetail->application_id    = $req->applicationId;
                $waterTranDetail->total_demand      = $req->amount;
                $waterTranDetail->save();
            }

            // Update SAF Payment Status
            if ($refWaterApplication['payment_status'] == false) {
                $activeSaf = WaterApplication::find($req['id']);
                $activeSaf->payment_status = 1;
                $activeSaf->save();
            }

            // Readjust Water Penalties
            $this->updatePenaltyPaymentStatus($req);
            DB::commit();
            return responseMsgs(true, "Payment Successfully Done",  ['TransactionNo' => $tranNo], "", "1.0", "ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * | Verify the requirements for the Offline payment
     * | Check the valid condition on application and req
     * | @param req
     * | @param refApplication
     * | @var mWaterPenaltyInstallment
     * | @var mWaterConnectionCharge
     * | @var penaltyIds
     * | @var refPenallty
     * | @var refPenaltySumAmount
     * | @var refAmount
     * | @var actualCharge
     * | @var actualAmount
     * | @var actualPenaltyAmount
     * | @var chargeAmount
        | Serial No : 07.01
     */
    public function verifyPaymentRules($req, $refApplication)
    {
        $mWaterPenaltyInstallment = new WaterPenaltyInstallment();
        $mWaterConnectionCharge = new WaterConnectionCharge();
        $paramChargeCatagory = Config::get('waterConstaint.CHARGE_CATAGORY');

        switch ($req) {
            case ($req->chargeCategory == $paramChargeCatagory['REGULAIZATION']):
                switch ($req) {
                    case ($req->isInstallment == "yes"):
                        $penaltyIds = $req->penaltyIds;
                        $refPenallty = $mWaterPenaltyInstallment->getPenaltyByArrayOfId($penaltyIds);
                        collect($refPenallty)->map(function ($value) {
                            if ($value['paid_status'] == 1) {
                                throw new Exception("payment for the respoctive Penaty has been done!");
                            }
                        });
                        $refPenaltySumAmount = collect($refPenallty)->map(function ($value) {
                            return $value['balance_amount'];
                        })->sum();
                        if ($refPenaltySumAmount != $req->penaltyAmount) {
                            throw new Exception("Respective Penalty Amount Not Matched!");
                        }

                        $actualCharge = $mWaterConnectionCharge->getWaterchargesById($req->applicationId)
                            ->where('charge_category', $req->chargeCategory)
                            ->firstOrFail();

                        if ($actualCharge['paid_status'] == false) {
                            $refAmount = $req->amount - $refPenaltySumAmount;
                            $actualAmount = $actualCharge['conn_fee'];
                            if ($actualAmount != $refAmount) {
                                throw new Exception("Connection Amount Not Matched!");
                            }
                        }
                        break;
                    case ($req->isInstallment == "no"): # check <-------------- calculation
                        $actualCharge = $mWaterConnectionCharge->getWaterchargesById($req->applicationId)
                            ->where('charge_category', $req->chargeCategory)
                            ->firstOrFail();

                        $refPenallty = $mWaterPenaltyInstallment->getPenaltyByApplicationId($req->applicationId)->get();
                        collect($refPenallty)->map(function ($value) {
                            if ($value['paid_status'] == 1) {
                                throw new Exception("payment for he respoctive Penaty has been done!");
                            }
                        });

                        $actualPenaltyAmount = (10 / 100 * $actualCharge['penalty']);
                        if ($req->penaltyAmount != $actualPenaltyAmount) {
                            throw new Exception("Penalty Amount Not Matched!");
                        }
                        $chargeAmount =  $actualCharge['amount'] - $actualPenaltyAmount;
                        if ($actualCharge['conn_fee'] != $chargeAmount) {
                            throw new Exception("Connection fee not matched!");
                        }
                        break;
                }
                break;

            case ($req->chargeCategory == $paramChargeCatagory['NEW_CONNECTION']):
                switch ($req) {
                    case (is_null($req->isInstallment) || !$req->isInstallment):
                        $actualCharge = $mWaterConnectionCharge->getWaterchargesById($req->applicationId)
                            ->where('charge_category', $req->chargeCategory)
                            ->firstOrFail();

                        $actualAmount = $actualCharge['amount'];
                        if ($actualAmount != $req->amount) {
                            throw new Exception("Connection Amount Not Matched!");
                        }
                        break;
                    case ($req->isInstallment == "yes"): # check <-------------- calculation
                        throw new Exception("No Installment in New Connection!");
                        break;
                }
                break;
        }
    }


    /**
     * | Post Other Payment Modes for Cheque,DD,Neft
        | Serial No : 07.02
     */
    public function postOtherPaymentModes($req)
    {
        $cash = Config::get('payment-constants.PAYMENT_MODE.3');
        $moduleId = Config::get('module-constants.WATER_MODULE_ID');
        $mTempTransaction = new TempTransaction();
        if ($req['paymentMode'] != $cash) {
            $mPropChequeDtl = new WaterChequeDtl();
            $chequeReqs = [
                'user_id' => $req['userId'],
                'prop_id' => $req['id'],
                'transaction_id' => $req['tranId'],
                'cheque_date' => $req['chequeDate'],
                'bank_name' => $req['bankName'],
                'branch_name' => $req['branchName'],
                'cheque_no' => $req['chequeNo']
            ];

            $mPropChequeDtl->postChequeDtl($chequeReqs);
        }

        $tranReqs = [
            'transaction_id' => $req['tranId'],
            'application_id' => $req['id'],
            'module_id' => $moduleId,
            'workflow_id' => $req['workflowId'],
            'transaction_no' => $req['tranNo'],
            'application_no' => $req['applicationNo'],
            'amount' => $req['amount'],
            'payment_mode' => $req['paymentMode'],
            'cheque_dd_no' => $req['chequeNo'],
            'bank_name' => $req['bankName'],
            'tran_date' => $req['todayDate'],
            'user_id' => $req['userId'],
            'ulb_id' => $req['ulbId']
        ];
        $mTempTransaction->tempTransaction($tranReqs);
    }

    /**
     * | Update the penalty Status 
     * | @param req
        | Serial No : 07.03
     */
    public function updatePenaltyPaymentStatus($req)
    {
        $mWaterPenaltyInstallment = new WaterPenaltyInstallment();
        switch ($req) {
            case (!empty($req->penaltyIds)):
                $mWaterPenaltyInstallment->updatePenaltyPayment($req->penaltyIds);
                break;

            case (is_null($req->penaltyIds) || empty($req->penaltyIds)):
                $mWaterPenaltyInstallment->getPenaltyByApplicationId($req->applicationId)
                    ->update([
                        'paid_status' => 1,
                    ]);
                break;
        }
    }
}
