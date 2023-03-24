<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Water\WaterConsumer as WaterWaterConsumer;
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
use App\Models\Water\WaterSiteInspectionsScheduling;
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
     * | @var redisConn
     * | @var returnValues
     * | @var mWaterParamPipelineType
     * | @var mWaterConnectionTypeMstr
     * | @var mWaterConnectionThroughMstr
     * | @var mWaterPropertyTypeMstr
     * | @var mWaterOwnerTypeMstr
     * | @var masterValues
     * | @var refMasterData
     * | @var configMasterValues
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

            # Ward Masters
            if (!$waterParamPipelineType) {
                $waterParamPipelineType = $mWaterParamPipelineType->getWaterParamPipelineType();                // Get PipelineType By Model Function
                $redisConn->set('water-param-pipeline-type', json_encode($waterParamPipelineType));             // Caching
            }

            if (!$waterConnectionTypeMstr) {
                $waterConnectionTypeMstr = $mWaterConnectionTypeMstr->getWaterConnectionTypeMstr();             // Get PipelineType By Model Function
                $redisConn->set('water-connection-type-mstr', json_encode($waterConnectionTypeMstr));           // Caching
            }

            if (!$waterConnectionThroughMstr) {
                $waterConnectionThroughMstr = $mWaterConnectionThroughMstr->getWaterConnectionThroughMstr();    // Get PipelineType By Model Function
                $redisConn->set('water-connection-through-mstr', json_encode($waterConnectionThroughMstr));     // Caching
            }

            if (!$waterPropertyTypeMstr) {
                $waterPropertyTypeMstr = $mWaterPropertyTypeMstr->getWaterPropertyTypeMstr();                   // Get PipelineType By Model Function
                $redisConn->set('water-property-type-mstr', json_encode($waterPropertyTypeMstr));               // Caching
            }

            if (!$waterOwnerTypeMstr) {
                $waterOwnerTypeMstr = $mWaterOwnerTypeMstr->getWaterOwnerTypeMstr();                            // Get PipelineType By Model Function
                $redisConn->set('water-owner-type-mstr', json_encode($waterOwnerTypeMstr));                     // Caching
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
            $configMasterValues = [
                "pipeline_size_type"    => $refMasterData['PIPELINE_SIZE_TYPE'],
                "pipe_diameter"         => $refMasterData['PIPE_DIAMETER'],
                "pipe_quality"          => $refMasterData['PIPE_QUALITY'],
                "road_type"             => $refMasterData['ROAD_TYPE'],
                "ferule_size"           => $refMasterData['FERULE_SIZE'],
                "deactivation_criteria" => $refMasterData['DEACTIVATION_CRITERIA'],
                "meter_connection_type" => $refMasterData['METER_CONNECTION_TYPE']
            ];
            $returnValues = collect($masterValues)->merge($configMasterValues);
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
            $mWaterConnectionCharge = new WaterConnectionCharge();
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

            $demandDetails = $mWaterConnectionCharge->getWaterchargesById($applicationId)
                ->whereIn('charge_category', ["New Connection", "Regulaization"])
                ->first();
            if ($demandDetails) {
                $fee = [
                    "conn_fee" => $demandDetails->conn_fee,
                    "penalty" => $demandDetails->penalty
                ];
            }

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
                "holdingNo" => $applicationDetails->holding_no,
                "safNo" => $applicationDetails->saf_no,
                "connectionFee" => $fee->conn_fee ?? $webhookDetails->payment_amount,
                "connectionPenalty" => $fee->penalty ?? "0.0",
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
     * | @var refTransactionNo
     * | @var mWaterConnectionCharge
     * | @var mWaterPenaltyInstallment
     * | @var mWaterApplication
     * | @var mWaterChequeDtl
     * | @var mWaterTran
     * | @var mTowards
     * | @var mAccDescription
     * | @var mDepartmentSection
     * | @var mPaymentModes
     * | @var transactionDetails
     * | @var applicationDetails
     * | @var connectionCharges
     * | @var individulePenaltyCharges
     * | @var refDate
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
                "holdingNo" => $applicationDetails['holding_no'],
                "safNo" => $applicationDetails['saf_no'],
                "paidUpto" => "",
                "paidUptoQtr" => "",
                "paymentMode" => $transactionDetails['payment_mode'],
                "bankName" => $chequeDetails[''] ?? null,                                   // in case of cheque,dd,nfts
                "branchName" => $chequeDetails[''] ?? null,                                 // in case of chque,dd,nfts
                "chequeNo" => $chequeDetails['']  ?? null,                                  // in case of chque,dd,nfts
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
        | Change/Check the Adjustment
     */
    public function saveSitedetails(siteAdjustment $request)
    {
        try {
            $mWaterSiteInspection = new WaterSiteInspection();
            $mWaterNewConnection = new WaterNewConnection();
            $mWaterConnectionCharge = new WaterConnectionCharge();
            $mWaterSiteInspectionsScheduling = new WaterSiteInspectionsScheduling();

            $connectionCatagory = Config::get('waterConstaint.CHARGE_CATAGORY');
            $waterDetails = WaterApplication::find($request->applicationId);

            # Check Related Condition
            $refRoleDetails = $this->CheckInspectionCondition($request, $waterDetails);

            # Get the Applied Connection Charge
            $applicationCharge = $mWaterConnectionCharge->getWaterchargesById($request->applicationId)
                ->where('charge_category', '!=', $connectionCatagory['SITE_INSPECTON'])
                ->firstOrFail();

            DB::beginTransaction();
            # Generating Demand for new InspectionData
            $newConnectionCharges = objToArray($mWaterNewConnection->calWaterConCharge($request));
            if (!$newConnectionCharges['status']) {
                throw new Exception(
                    $newConnectionCharges['errors']
                );
            }
            # Param Value for the new Charges
            $waterFeeId = $newConnectionCharges['water_fee_mstr_id'];

            # If the Adjustment Hamper
            $this->adjustmentInConnection($request, $newConnectionCharges, $waterDetails, $applicationCharge);

            # Store the site inspection details
            $mWaterSiteInspection->storeInspectionDetails($request, $waterFeeId, $waterDetails, $refRoleDetails);
            $mWaterSiteInspectionsScheduling->saveInspectionStatus($request);
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
        return $roleId;
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
    public function adjustmentInConnection($request, $newConnectionCharges, $waterApplicationDetails, $applicationCharge)
    {
        $applicationId = $request->applicationId;
        $newCharge = $newConnectionCharges['conn_fee_charge']['amount'];
        $mWaterPenaltyInstallment = new WaterPenaltyInstallment();
        $mWaterConnectionCharge = new WaterConnectionCharge();
        $mWaterApplication = new WaterApplication();
        $chargeCatagory = Config::get('waterConstaint.CHARGE_CATAGORY');
        $refInstallment['penalty_head'] = Config::get('waterConstaint.PENALTY_HEAD.1');

        # connection charges
        $request->merge([
            'chargeCatagory'    => $chargeCatagory['SITE_INSPECTON'],
            'connectionType'    => $chargeCatagory['SITE_INSPECTON'],
            'ward_id'           => $waterApplicationDetails['ward_id']
        ]);

        # in case of connection charge is not 0
        switch ($newCharge) {
            case ($newCharge != 0):
                # cherge changes 
                if ($newConnectionCharges['conn_fee_charge']['conn_fee'] > $applicationCharge['conn_fee']) {
                    $adjustedConnFee = $newConnectionCharges['conn_fee_charge']['conn_fee'] - $applicationCharge['conn_fee'];
                    $newConnectionCharges['conn_fee_charge']['conn_fee'] = $adjustedConnFee;

                    # get Water Application Penalty 
                    if ($newConnectionCharges['conn_fee_charge']['penalty'] > $applicationCharge['penalty']) {
                        $unpaidPenalty = $this->checkOldPenalty($applicationId, $chargeCatagory);
                        $calculetedPenalty = $newConnectionCharges['conn_fee_charge']['penalty'] - $applicationCharge['penalty'];
                        $refInstallment['installment_amount'] = $calculetedPenalty + $unpaidPenalty;
                        $refInstallment['balance_amount'] =  $refInstallment['installment_amount'];

                        $mWaterPenaltyInstallment->deactivateOldPenalty($request, $applicationId, $chargeCatagory);
                        // $mWaterPenaltyInstallment->saveWaterPenelty($applicationId, $refInstallment, $chargeCatagory['SITE_INSPECTON']);
                    }
                    # for the Case of no extra penalty 
                    $unpaidPenalty = $this->checkOldPenalty($applicationId, $chargeCatagory);
                    if ($unpaidPenalty != 0) {
                        $refInstallment['installment_amount'] = $unpaidPenalty;
                        $refInstallment['balance_amount'] =  $refInstallment['installment_amount'];
                        $newConnectionCharges['conn_fee_charge']['penalty'] = $refInstallment['installment_amount'] ?? 0;

                        $mWaterPenaltyInstallment->deactivateOldPenalty($request, $applicationId, $chargeCatagory);
                        // $mWaterPenaltyInstallment->saveWaterPenelty($applicationId, $refInstallment, $chargeCatagory['SITE_INSPECTON']);
                    }
                    # if there is no old penalty and all penalty is paid
                    if ($newConnectionCharges['conn_fee_charge']['penalty'] == 0) {
                        $mWaterPenaltyInstallment->deactivateOldPenalty($request, $applicationId, $chargeCatagory);
                    }

                    $newConnectionCharges['conn_fee_charge']['amount'] = $newConnectionCharges['conn_fee_charge']['penalty'] + $newConnectionCharges['conn_fee_charge']['conn_fee'];
                    $connectionId = $mWaterConnectionCharge->saveWaterCharge($applicationId, $request, $newConnectionCharges);
                    $mWaterPenaltyInstallment->saveWaterPenelty($applicationId, $refInstallment, $chargeCatagory['SITE_INSPECTON'], $connectionId);
                    $mWaterApplication->updatePaymentStatus($applicationId, false);
                    break;
                }
                # in case of no change in connection charges but the old penalty is unpaid
                $unpaidPenalty = $this->checkOldPenalty($applicationId, $chargeCatagory);
                if ($unpaidPenalty != 0) {
                    $refInstallment['installment_amount'] = $unpaidPenalty;
                    $refInstallment['balance_amount'] =  $unpaidPenalty;
                    $newConnectionCharges['conn_fee_charge']['penalty'] = $unpaidPenalty;

                    $mWaterPenaltyInstallment->deactivateOldPenalty($request, $applicationId, $chargeCatagory);
                    // $mWaterPenaltyInstallment->saveWaterPenelty($applicationId, $refInstallment, $chargeCatagory['SITE_INSPECTON']);

                    # Static Connection fee
                    $newConnectionCharges['conn_fee_charge']['conn_fee'] = 0;
                    $newConnectionCharges['conn_fee_charge']['amount'] = $unpaidPenalty;
                    $connectionId = $mWaterConnectionCharge->saveWaterCharge($applicationId, $request, $newConnectionCharges);
                    $mWaterPenaltyInstallment->saveWaterPenelty($applicationId, $refInstallment, $chargeCatagory['SITE_INSPECTON'], $connectionId);
                }
                break;
            case ($newCharge == 0):
                $mWaterPenaltyInstallment->deactivateOldPenalty($request, $applicationId, $chargeCatagory);
                $mWaterApplication->updatePaymentStatus($applicationId, true);
                break;
        }
    }


    /**
     * | Check for the old penalty 
     * | @param applicationID
     * | @param chargeCatagory
        | Serial No : 04.02.01
        | Working
     */
    public function checkOldPenalty($applicationId, $chargeCatagory)
    {
        $mWaterPenaltyInstallment = new WaterPenaltyInstallment();
        $oldPenalty = $mWaterPenaltyInstallment->getPenaltyByApplicationId($applicationId)
            ->where('water_penalty_installments.payment_from', '!=', $chargeCatagory['SITE_INSPECTON'])
            ->get();
        $unpaidPenalty = collect($oldPenalty)->map(function ($value) {
            if ($value['paid_status'] == 0) {
                return $value['balance_amount'];
            }
        })->sum();
        return $unpaidPenalty;
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
    public function offlineDemandPayment(Request $request)
    {
        try {
            
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", $request->deviceId);
        }
    }

    /**
     * | 
        | Not Working
     */
    public function preOfflinePaymentParams($request)
    {
        $mWaterConsumerDemand = new WaterConsumerDemand();
        $refConsumer = WaterConsumer::find($request->id);
        $consumerId = $request->consumerId;
        if (!$refConsumer) {
            throw new Exception("Consumer Not Found!");
        }
        $allCharges = $mWaterConsumerDemand->getFirstConsumerDemand($consumerId)
            ->where('demand_from', '>=', $request->demandFrom)
            ->where('demand_upto', '<=', $request->demandUpto)
            ->get();
        $checkCharges = collect($allCharges)->last();
        if (!$checkCharges->id) {
            throw new Exception("Chrges for respective date dont exise!......");
        }
        return [
            "consumer" => $refConsumer,
            "consumerCahges" => $allCharges,
        ];
    }


    /**
     * | Calculate the Demand for the respective Consumer
     * | @param request request
        | Working
        | Serial No : 
     */
    public function callDemandByMonth(Request $request)
    {
        $request->validate([
            'consumerId' => 'required',
            'demandFrom' => 'required|date|date_format:Y-m-d',
            'demandUpto' => 'required|date|date_format:Y-m-d',
        ]);
        try {
            $collectiveCharges = $this->checkCallParams($request);

            $returnData['totalPayAmount'] = collect($collectiveCharges)->pluck('balance_amount')->sum();
            $returnData['totalPenalty'] = collect($collectiveCharges)->pluck('penalty')->sum();
            $returnData['toalDemand'] = collect($collectiveCharges)->pluck('amount')->sum();
            return responseMsgs(true, "Amount Details!", remove_null($returnData), "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", $request->deviceId);
        }
    }

    /**
     * | calling functon for checking params for callculating demand according to month
     * | @param request
        | Serial No :
        | Working  
     */
    public function checkCallParams($request)
    {
        $consumerDetails = WaterConsumerDemand::find($request->consumerId);
        if (!$consumerDetails) {
            throw new Exception("Consumer dont exist!");
        }
        $mWaterConsumerDemand = new WaterConsumerDemand();
        $allCharges = $mWaterConsumerDemand->getFirstConsumerDemand($request->consumerId)
            ->where('demand_from', '>=', $request->demandFrom)
            ->where('demand_upto', '<=', $request->demandUpto)
            ->get();

        $checkDemand = collect($allCharges)->first()->id;
        if (!$checkDemand) {
            throw new Exception("Demand according to date not found!");
        }
        return $allCharges;
    }


    /**
     * | Online Payment for the consumer Demand
     * | Data After the Webhook Payment / Called by the Webhook
     * | @param
        | Serial No : 06
        | Recheck / Not Working
     */
    // public function endOnlineDemandPayment($args)
    // {
    //     try {
    //         $refUser        = Auth()->user();
    //         $refUserId      = $refUser->id ?? $args["userId"];
    //         $refUlbId       = $refUser->ulb_id ?? $args["ulbId"];
    //         $mNowDate       = Carbon::now()->format('Y-m-d');
    //         $mTimstamp      = Carbon::now()->format('Y-m-d H:i:s');
    //         $cahges         = null;
    //         $chargeData     = (array)null;
    //         $application    = null;
    //         $mDemands       = (array)null;

    //         #-----------valication------------------- 
    //         $RazorPayRequest = WaterRazorPayRequest::select("*")
    //             ->where("order_id", $args["orderId"])
    //             ->where("related_id", $args["id"])
    //             ->where("status", 2)
    //             ->first();
    //         if (!$RazorPayRequest) {
    //             throw new Exception("Data Not Found");
    //         }
    //         if ($RazorPayRequest->payment_from == "New Connection") {
    //             $application = WaterApplication::find($args["id"]);
    //             $cahges = 0;
    //             $id = explode(",", $RazorPayRequest->demand_from_upto);
    //             if ($id) {
    //                 $mDemands = WaterConnectionCharge::select("*")
    //                     ->whereIn("id", $id)
    //                     ->get();
    //                 $cahges = ($mDemands->sum("amount"));
    //             }
    //             $chargeData["total_charge"] = $cahges;
    //         } elseif ($RazorPayRequest->payment_from == "Demand Collection") {
    //             $application = null;
    //         }
    //         if (!$application) {
    //             throw new Exception("Application Not Found!......");
    //         }
    //         $applicationId = $args["id"];
    //         #-----------End valication----------------------------

    //         #-------------Calculation----------------------------- 
    //         if (!$chargeData || round($args['amount']) != round($chargeData['total_charge'])) {
    //             throw new Exception("Payble Amount Missmatch!!!");
    //         }

    //         $transactionType = $RazorPayRequest->payment_from;

    //         $totalCharge = $chargeData['total_charge'];
    //         #-------------End Calculation-----------------------------
    //         #-------- Transection -------------------
    //         DB::beginTransaction();

    //         $RazorPayResponse = new WaterRazorPayResponse;
    //         $RazorPayResponse->related_id   = $RazorPayRequest->related_id;
    //         $RazorPayResponse->request_id   = $RazorPayRequest->id;
    //         $RazorPayResponse->amount       = $args['amount'];
    //         $RazorPayResponse->merchant_id  = $args['merchantId'] ?? null;
    //         $RazorPayResponse->order_id     = $args["orderId"];
    //         $RazorPayResponse->payment_id   = $args["paymentId"];
    //         $RazorPayResponse->save();

    //         $RazorPayRequest->status = 1;
    //         $RazorPayRequest->update();

    //         $Tradetransaction = new WaterTran;
    //         $Tradetransaction->related_id       = $applicationId;
    //         $Tradetransaction->ward_id          = $application->ward_id;
    //         $Tradetransaction->tran_type        = $transactionType;
    //         $Tradetransaction->tran_date        = $mNowDate;
    //         $Tradetransaction->payment_mode     = "Online";
    //         $Tradetransaction->amount           = $totalCharge;
    //         $Tradetransaction->emp_dtl_id       = $refUserId;
    //         $Tradetransaction->created_at       = $mTimstamp;
    //         $Tradetransaction->ip_address       = '';
    //         $Tradetransaction->ulb_id           = $refUlbId;
    //         $Tradetransaction->save();
    //         $transaction_id                     = $Tradetransaction->id;
    //         $Tradetransaction->tran_no          = $args["transactionNo"];
    //         $Tradetransaction->update();

    //         foreach ($mDemands as $val) {
    //             $TradeDtl = new WaterTranDetail;
    //             $TradeDtl->tran_id        = $transaction_id;
    //             $TradeDtl->demand_id      = $val->id;
    //             $TradeDtl->total_demand   = $val->amount;
    //             $TradeDtl->application_id   = $val->application_id;
    //             $TradeDtl->created_at     = $mTimstamp;
    //             $TradeDtl->save();

    //             $val->paid_status = true;
    //             $val->update();
    //         }

    //         $application->payment_status = true;
    //         $application->update();
    //         ////////////////////////////////////////
    //         # Check 
    //         WaterApplication::where('id', $applicationId)
    //             ->update([
    //                 'current_role' => $this->_dealingAssistent
    //             ]);
    //         /////////////////////////////////////////
    //         DB::commit();
    //         #----------End transaction------------------------
    //         #----------Response------------------------------
    //         $res['transactionId'] = $transaction_id;
    //         $res['paymentRecipt'] = config('app.url') . "/api/water/paymentRecipt/" . $applicationId . "/" . $transaction_id;
    //         return responseMsg(true, "", $res);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return responseMsg(false, $e->getMessage(), $args);
    //     }
    // }


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
            $offlinePaymentModes = Config::get('payment-constants.VERIFICATION_PAYMENT_MODE');
            $todayDate = Carbon::now();
            $mWaterApplication = new WaterApplication();
            $mWaterConnectionCharge = new WaterConnectionCharge();
            $idGeneration = new IdGeneration;
            $waterTran = new WaterTran();
            $userId = auth()->user()->id;
            $userType = authUser()->user_type;                                                         # Authenticated user or Ghost User
            $refWaterApplication = $mWaterApplication->getApplicationById($req->applicationId)
                ->firstOrFail();

            # check the pre requirement 
            $this->verifyPaymentRules($req, $refWaterApplication);

            # Derivative Assignments
            $tranNo = $idGeneration->generateTransactionNo();
            $charges = $mWaterConnectionCharge->getWaterchargesById($req->applicationId)
                ->where('paid_status', 0)
                ->get();                                                                                        # get water User connectin charges

            if (!$charges || collect($charges)->isEmpty())
                throw new Exception("Connection Not Available for Payment!");
            # Water Transactions
            $req->merge([
                'userId'    => $userId,
                'userType'  => $userType,
                'todayDate' => $todayDate->format('Y-m-d'),
                'tranNo'    => $tranNo,
                'id'        => $req->applicationId,
                'ulbId'     => authUser()->ulb_id,
            ]);
            DB::beginTransaction();
            # Save the Details of the transaction
            $wardId['ward_mstr_id'] = $refWaterApplication['ward_id'];
            $waterTrans = $waterTran->waterTransaction($req, $wardId);

            # Save the Details for the Cheque,DD,nfet
            if (in_array($req['paymentMode'], $offlinePaymentModes)) {
                $req->merge([
                    'chequeDate'    => $req['chequeDate'],
                    'tranId'        => $waterTrans['id'],
                    'id'            => $req->applicationId,
                    'applicationNo' => $refWaterApplication['application_no'],
                    'workflowId'    => $refWaterApplication['workflow_id'],
                    'ward_no'       => $refWaterApplication['ward_id']
                ]);
                $this->postOtherPaymentModes($req);
            }

            # Reflect on water Tran Details
            foreach ($charges as $charges) {
                $this->savePaymentStatus($req, $offlinePaymentModes, $charges, $refWaterApplication, $waterTrans);
            }


            # Readjust Water Penalties
            $this->updatePenaltyPaymentStatus($req);
            DB::commit();
            return responseMsgs(true, "Payment Successfully Done",  ['TransactionNo' => $tranNo], "", "1.0", "ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * | Save the payment status for respective payment
     * | @param req
        | Serial No : 07.04
        | Working
     */
    public function savePaymentStatus($req, $offlinePaymentModes, $charges, $refWaterApplication, $waterTrans)
    {
        $mWaterApplication = new WaterApplication();
        if (in_array($req['paymentMode'], $offlinePaymentModes)) {
            $charges->paid_status = 2;
            $mWaterApplication->updatePendingStatus($req['id']);
        } else {
            $charges->paid_status = 1;                                      // <-------- Update Demand Paid Status 

            if ($refWaterApplication['payment_status'] == 0) {              // <----------- Update Water Application Payment Status
                $mWaterApplication->updateOnlyPaymentstatus($req['id']);
            }
        }
        $charges->save();

        $waterTranDetail = new WaterTranDetail();
        $waterTranDetail->saveDefaultTrans(
            $req->amount,
            $req->applicationId,
            $waterTrans['id'],
            $charges['id'],
        );
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
        $connectionTypeIdConfig = Config::get('waterConstaint.CONNECTION_TYPE');

        switch ($req) {
                # In Case of Residential payment Offline
            case ($req->chargeCategory == $paramChargeCatagory['REGULAIZATION']):
                if ($refApplication['connection_type_id'] != $connectionTypeIdConfig['REGULAIZATION']) {
                    throw new Exception("The respective application in not for Regulaization!");
                }
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

                        if ($actualCharge['paid_status'] == 0) {
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

                # In Case of New Connection payment Offline
            case ($req->chargeCategory == $paramChargeCatagory['NEW_CONNECTION']):
                if ($refApplication['connection_type_id'] != $connectionTypeIdConfig['NEW_CONNECTION']) {
                    throw new Exception("The respective application in not for New Connection!");
                }
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
                    case ($req->isInstallment == "yes"):
                        throw new Exception("No Installment in New Connection!");
                        break;
                }
                break;

                # In case of Site Inspection
            case ($req->chargeCategory == $paramChargeCatagory['SITE_INSPECTON']):
                $actualCharge = $mWaterConnectionCharge->getWaterchargesById($req->applicationId)
                    ->where('charge_category', $paramChargeCatagory['SITE_INSPECTON'])
                    ->where('paid_status', 0)
                    ->firstOrFail();
                if ($actualCharge['amount'] != $req->amount) {
                    throw new Exception("Amount Not Matched!");
                }
                if ($req->isInstallment == "yes") {
                    throw new Exception("No Installment in Site Inspection Charges!");
                }
                break;
        }
    }


    /**
     * | Post Other Payment Modes for Cheque,DD,Neft
     * | @param req
        | Serial No : 07.02
        | Working
     */
    public function postOtherPaymentModes($req)
    {
        $cash = Config::get('payment-constants.PAYMENT_MODE.3');
        $moduleId = Config::get('module-constants.WATER_MODULE_ID');
        $mTempTransaction = new TempTransaction();

        if ($req['paymentMode'] != $cash) {
            $mPropChequeDtl = new WaterChequeDtl();
            $chequeReqs = [
                'user_id'           => $req['userId'],
                'application_id'    => $req['id'],
                'transaction_id'    => $req['tranId'],
                'cheque_date'       => $req['chequeDate'],
                'bank_name'         => $req['bankName'],
                'branch_name'       => $req['branchName'],
                'cheque_no'         => $req['chequeNo']
            ];

            $mPropChequeDtl->postChequeDtl($chequeReqs);
        }

        $tranReqs = [
            'transaction_id'    => $req['tranId'],
            'application_id'    => $req['id'],
            'module_id'         => $moduleId,
            'workflow_id'       => $req['workflowId'],
            'transaction_no'    => $req['tranNo'],
            'application_no'    => $req['applicationNo'],
            'amount'            => $req['amount'],
            'payment_mode'      => strtoupper($req['paymentMode']),
            'cheque_dd_no'      => $req['chequeNo'],
            'bank_name'         => $req['bankName'],
            'tran_date'         => $req['todayDate'],
            'user_id'           => $req['userId'],
            'ulb_id'            => $req['ulbId'],
            'ward_id'           => $req['ward_id']
        ];
        $mTempTransaction->tempTransaction($tranReqs);
    }

    /**
     * | Update the penalty Status 
     * | @param req
     * | @var mWaterPenaltyInstallment
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


    /**
     * | Get the payment history for the Application
     * | @param request
     * | @var 
     * | @return 
        | Serial No : 08
        | Working
     */
    public function getApplicationPaymentHistory(Request $request)
    {
        $request->validate([
            'id' => 'required|digits_between:1,9223372036854775807'
        ]);
        try {
            $mWaterTran = new WaterTran();
            $mWaterApplication = new WaterApplication();
            $mWaterConnectionCharge = new WaterConnectionCharge();
            $mWaterPenaltyInstallment = new WaterPenaltyInstallment();

            $transactions = array();
            $applicationId = $request->id;

            # Application Details
            $waterDtls = $mWaterApplication->getDetailsByApplicationId($applicationId)->first();
            if (!$waterDtls)
                throw new Exception("Water Application Not Found!");

            # if demand transaction exist
            $connectionTran = $mWaterTran->getTransNo($applicationId, null)->get();                        // Water Connection payment History
            $checkTrans = collect($connectionTran)->first();
            if (!$checkTrans)
                throw new Exception("Water Application Tran Details not Found!!");

            # Connection Charges And Penalty
            $refConnectionDetails = $mWaterConnectionCharge->getWaterchargesById($applicationId)->get();
            $penaltyList = collect($refConnectionDetails)->map(function ($value, $key)
            use ($mWaterPenaltyInstallment, $applicationId) {
                if ($value['penalty'] > 0) {
                    $penaltyList = $mWaterPenaltyInstallment->getPenaltyByApplicationId($applicationId)
                        ->where('payment_from', $value['charge_category'])
                        ->get();

                    #check the penalty paid status
                    $checkPenalty = collect($penaltyList)->map(function ($penaltyList) {
                        if ($penaltyList['paid_status'] == 0) {
                            return false;
                        }
                        return true;
                    });
                    switch ($checkPenalty) {
                        case ($checkPenalty->contains(false)):
                            $penaltyPaymentStatus = false;
                            break;
                        default:
                            $penaltyPaymentStatus = true;
                            break;
                    }

                    # collect the penalty amount to be paid 
                    $penaltyAmount = collect($penaltyList)->map(function ($secondvalue) {
                        if ($secondvalue['paid_status'] == 0) {
                            return $secondvalue['balance_amount'];
                        }
                    })->filter()->sum();

                    # return data
                    if ($penaltyPaymentStatus == 0 || $value['paid_status'] == 0) {
                        $status['penaltyPaymentStatus']     = $penaltyPaymentStatus ?? null;
                        $status['chargeCatagory']           = $value['charge_category'];
                        $status['penaltyAmount']            = $penaltyAmount;
                        return $status;
                    }
                }
            })->filter();
            # return Data
            $transactions = [
                "transactionHistory" => collect($connectionTran)->sortByDesc('id')->values(),
                "paymentList" => $penaltyList->values()->first()
            ];
            return responseMsgs(true, "", remove_null($transactions), "", "01", "ms", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
        }
    }
}
