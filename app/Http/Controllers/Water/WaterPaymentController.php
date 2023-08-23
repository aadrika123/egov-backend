<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Http\Requests\Water\reqConsumerReqPayment;
use App\Http\Requests\Water\reqDemandPayment;
use App\Http\Requests\Water\ReqWaterPayment;
use App\Http\Requests\Water\siteAdjustment;
use App\MicroServices\IdGeneration;
use App\Models\Payment\TempTransaction;
use App\Models\Payment\WebhookPaymentData;
use App\Models\Water\WaterAdjustment;
use App\Models\Water\WaterAdvance;
use App\Models\Water\WaterApplication;
use Illuminate\Support\Facades\Validator;
use App\Models\Water\WaterApprovalApplicationDetail;
use App\Models\Water\WaterChequeDtl;
use App\Models\Water\WaterConnectionCharge;
use App\Models\Water\WaterConnectionThroughMstr;
use App\Models\Water\WaterConnectionTypeMstr;
use App\Models\Water\WaterConsumer;
use App\Models\Water\WaterConsumerActiveRequest;
use App\Models\Water\WaterConsumerCharge;
use App\Models\Water\WaterConsumerChargeCategory;
use App\Models\Water\WaterConsumerCollection;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterConsumerInitialMeter;
use App\Models\Water\WaterConsumerMeter;
use App\Models\Water\WaterConsumerTax;
use App\Models\Water\WaterOwnerTypeMstr;
use App\Models\Water\WaterParamPipelineType;
use App\Models\Water\WaterPenaltyInstallment;
use App\Models\Water\WaterPropertyTypeMstr;
use App\Models\Water\WaterRazorPayRequest;
use App\Models\Water\WaterRazorPayResponse;
use App\Models\Water\WaterSiteInspection;
use App\Models\Water\WaterSiteInspectionsScheduling;
use App\Models\Water\WaterTran;
use App\Models\Water\WaterTranDetail;
use App\Models\Water\WaterTranFineRebate;
use App\Models\Workflows\WfRoleusermap;
use App\Repository\Water\Concrete\WaterNewConnection;
use App\Traits\Payment\Razorpay;
use App\Traits\Ward;
use App\Traits\Water\WaterTrait;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Predis\Command\Redis\SELECT;

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
    use Razorpay;
    use WaterTrait;

    // water Constant
    private $_waterRoles;
    private $_waterMasterData;
    private $_towards;
    private $_consumerTowards;
    private $_accDescription;
    private $_departmentSection;
    private $_paymentModes;

    public function __construct()
    {
        $this->_waterRoles          = Config::get('waterConstaint.ROLE-LABEL');
        $this->_waterMasterData     = Config::get('waterConstaint.WATER_MASTER_DATA');
        $this->_towards             = Config::get('waterConstaint.TOWARDS');
        $this->_consumerTowards     = Config::get('waterConstaint.TOWARDS_DEMAND');
        $this->_accDescription      = Config::get('waterConstaint.ACCOUNT_DESCRIPTION');
        $this->_departmentSection   = Config::get('waterConstaint.DEPARTMENT_SECTION');
        $this->_paymentModes        = Config::get('payment-constants.PAYMENT_OFFLINE_MODE');
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
        | Working
     */
    public function getWaterMasterData()
    {
        try {
            $redisConn = Redis::connection();
            $returnValues = [];

            $mWaterParamPipelineType        = new WaterParamPipelineType();
            $mWaterConnectionTypeMstr       = new WaterConnectionTypeMstr();
            $mWaterConnectionThroughMstr    = new WaterConnectionThroughMstr();
            $mWaterPropertyTypeMstr         = new WaterPropertyTypeMstr();
            $mWaterOwnerTypeMstr            = new WaterOwnerTypeMstr();
            $mWaterConsumerChargeCategory   = new WaterConsumerChargeCategory();

            $waterParamPipelineType     = json_decode(Redis::get('water-param-pipeline-type'));
            $waterConnectionTypeMstr    = json_decode(Redis::get('water-connection-type-mstr'));
            $waterConnectionThroughMstr = json_decode(Redis::get('water-connection-through-mstr'));
            $waterPropertyTypeMstr      = json_decode(Redis::get('water-property-type-mstr'));
            $waterOwnerTypeMstr         = json_decode(Redis::get('water-owner-type-mstr'));
            $waterConsumerChargeMstr    = json_decode(Redis::get('water-consumer-charge-mstr'));

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
            if (!$waterConsumerChargeMstr) {
                $waterConsumerChargeMstr = $mWaterConsumerChargeCategory->getConsumerChargesType();
                $redisConn->set('water-consumer-charge-mstr', json_encode($waterConsumerChargeMstr));
            }
            $masterValues = [
                'water_param_pipeline_type'     => $waterParamPipelineType,
                'water_connection_type_mstr'    => $waterConnectionTypeMstr,
                'water_connection_through_mstr' => $waterConnectionThroughMstr,
                'water_property_type_mstr'      => $waterPropertyTypeMstr,
                'water_owner_type_mstr'         => $waterOwnerTypeMstr,
                'water_consumer_charge_mstr'    => $waterConsumerChargeMstr
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
        $validated = Validator::make(
            $request->all(),
            [
                'consumerId' => 'required|digits_between:1,9223372036854775807'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $mWaterTran             = new WaterTran();
            $mWaterConsumer         = new WaterConsumer();
            $mWaterConsumerDemand   = new WaterConsumerDemand();
            $mWaterTranDetail       = new WaterTranDetail();
            $transactions           = array();

            # consumer Details
            $waterDtls = $mWaterConsumer->getConsumerDetailById($request->consumerId);
            if (!$waterDtls)
                throw new Exception("Water Consumer Not Found!");

            # if Consumer in made vie application
            $applicationId = $waterDtls->apply_connection_id;
            if (!$applicationId)
                throw new Exception("This Consumer has not ApplicationId!!");

            # if demand transactions exist
            $connectionTran = $mWaterTran->getTransNo($applicationId, null)->get();                        // Water Connection payment History
            $connectionTran = collect($connectionTran)->sortByDesc('id')->values();
            if (!$connectionTran->first() || is_null($connectionTran))
                throw new Exception("Water Application's Transaction Details not Found!!");

            # Application transactions
            $waterTrans = $mWaterTran->ConsumerTransaction($request->consumerId)->get();         // Water Consumer Payment History
            $waterTrans = collect($waterTrans)->map(function ($value) use ($mWaterConsumerDemand, $mWaterTranDetail) {
                $demandId = $mWaterTranDetail->getDetailByTranId($value['id']);
                $value['demand'] = $mWaterConsumerDemand->getDemandBydemandId($demandId['demand_id']);
                return $value;
            })->sortByDesc('id')->values();

            $transactions['Consumer'] = $waterTrans;
            $transactions['connection'] = $connectionTran;

            return responseMsgs(true, "", remove_null($transactions), "", "01", "ms", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
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
        | Working
        | Recheck
     */
    public function generateOfflinePaymentReceipt(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'transactionNo' => 'required'
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $refTransactionNo = $req->transactionNo;

            $mWaterConnectionCharge             = new WaterConnectionCharge();
            $mWaterPenaltyInstallment           = new WaterPenaltyInstallment();
            $mWaterApplication                  = new WaterApplication();
            $mWaterApprovalApplicationDetail    = new WaterApprovalApplicationDetail();
            $mWaterChequeDtl                    = new WaterChequeDtl();
            $mWaterTran                         = new WaterTran();
            $mWaterTranFineRebate               = new WaterTranFineRebate();

            $mTowards           = $this->_towards;
            $mAccDescription    = $this->_accDescription;
            $mDepartmentSection = $this->_departmentSection;
            $mPaymentModes      = $this->_paymentModes;
            $mSearchForRebate   = Config::get("waterConstaint.PENALTY_HEAD");

            # transaction Deatils
            $transactionDetails = $mWaterTran->getTransactionByTransactionNo($refTransactionNo)
                ->first();
            if (!$transactionDetails) {
                throw new Exception("Data according to transaction no is not found!");
            }
            #  Data not equal to Cash
            if (!in_array($transactionDetails['payment_mode'], [$mPaymentModes['1'], $mPaymentModes['5']])) {
                $chequeDetails = $mWaterChequeDtl->getChequeDtlsByTransId($transactionDetails['id'])->firstOrFail();
            }
            # Application Deatils
            $applicationDetails = $mWaterApplication->getDetailsByApplicationId($transactionDetails->related_id)->first();
            if (is_null($applicationDetails)) {
                $applicationDetails = $mWaterApprovalApplicationDetail->getApprovedApplicationById($transactionDetails->related_id)->firstOrFail();
            }
            # Connection Charges
            $connectionCharges = $mWaterConnectionCharge->getChargesById($transactionDetails->demand_id)
                ->first();

            # if penalty Charges
            if ($transactionDetails->penalty_ids) {
                $penaltyIds = explode(',', $transactionDetails->penalty_ids);
                $refPenalty = $mWaterPenaltyInstallment->getPenaltyByArrayOfId($penaltyIds);
                $totalPenaltyAmount = collect($refPenalty)->sum('balance_amount');

                # check and find for rebate
                $refRebaterDetails = $mWaterTranFineRebate->getFineRebate($applicationDetails['id'], $mSearchForRebate['4'], $transactionDetails['id'])->first();
                if (!is_null($refRebaterDetails)) {
                    $rebateAmount = $refRebaterDetails['amount'];
                }
            }

            # Transaction Date
            $refDate = $transactionDetails->tran_date;
            $transactionDate = Carbon::parse($refDate)->format('Y-m-d');

            $returnValues = [
                "departmentSection"     => $mDepartmentSection,
                "accountDescription"    => $mAccDescription,
                "transactionDate"       => $transactionDate,
                "transactionNo"         => $refTransactionNo,
                "applicationNo"         => $applicationDetails['application_no'],
                "customerName"          => $applicationDetails['applicantname'],
                "customerMobile"        => $applicationDetails['mobileno'],
                "address"               => $applicationDetails['address'],
                "paidFrom"              => $connectionCharges['charge_category'] ?? $transactionDetails['tran_type'],
                "holdingNo"             => $applicationDetails['holding_no'],
                "safNo"                 => $applicationDetails['saf_no'],
                "paidUpto"              => "",
                "paymentMode"           => $transactionDetails['payment_mode'],
                "bankName"              => $chequeDetails['bank_name'] ?? null,                                  // in case of cheque,dd,nfts
                "branchName"            => $chequeDetails['branch_name'] ?? null,                                  // in case of chque,dd,nfts
                "chequeNo"              => $chequeDetails['cheque_no'] ?? null,                                  // in case of chque,dd,nfts
                "chequeDate"            => $chequeDetails['cheque_date'] ?? null,                                  // in case of chque,dd,nfts
                "monthlyRate"           => "",
                "demandAmount"          => $transactionDetails->amount,
                "taxDetails"            => "",
                "ulbId"                 => $transactionDetails['ulb_id'],
                "ulbName"               => $applicationDetails['ulb_name'],
                "WardNo"                => $applicationDetails['ward_name'],
                "logo"                  => $applicationDetails['logo'],
                "towards"               => $mTowards,
                "description"           => $mAccDescription,
                "rebate"                => $rebateAmount ?? 0,                                                           // Static
                "connectionFee"         => $connectionCharges['conn_fee'] ?? 0,
                "totalPaidAmount"       => $transactionDetails->amount,
                "penaltyAmount"         => $totalPenaltyAmount ?? 0,
                "paidAmtInWords"        => getIndianCurrency($transactionDetails->amount),
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
     * | @return
        | Serial No : 04
        | Working
        | Check the Adjustment
     */
    public function saveSitedetails(siteAdjustment $request)
    {
        try {
            $mWaterSiteInspection   = new WaterSiteInspection();
            $mWaterNewConnection    = new WaterNewConnection();
            $mWaterConnectionCharge = new WaterConnectionCharge();
            $mWaterSiteInspectionsScheduling = new WaterSiteInspectionsScheduling();

            $connectionCatagory = Config::get('waterConstaint.CHARGE_CATAGORY');
            $waterDetails = WaterApplication::findOrFail($request->applicationId);

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
            $paymentstatus = $this->adjustmentInConnection($request, $newConnectionCharges, $waterDetails, $applicationCharge);

            # Store the site inspection details
            $mWaterSiteInspection->storeInspectionDetails($request, $waterFeeId, $waterDetails, $refRoleDetails, $paymentstatus);
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
        | Common function
     */
    public function CheckInspectionCondition($request, $waterDetails)
    {
        $mWfRoleUsermap = new WfRoleusermap();
        $waterRoles = $this->_waterRoles;

        # check the login user is Eo or not
        $userId = authUser($request)->id;
        $workflowId = $waterDetails->workflow_id;
        $getRoleReq = new Request([                                                 # make request to get role id of the user
            'userId'     => $userId,
            'workflowId' => $workflowId
        ]);
        $readRoleDtls = $mWfRoleUsermap->getRoleByUserWfId($getRoleReq);
        $roleId = $readRoleDtls->wf_role_id;

        # Checking Condition
        if ($roleId != $waterRoles['JE']) {
            throw new Exception("You are not Junier Enginer!");
        }
        if ($waterDetails->current_role != $waterRoles['JE']) {
            throw new Exception("Application Is Not under Junier Enginer!");
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
     * | @param waterApplicationDetails
     * | @param applicationCharge
        | Serial No : 04.02
        | Working
        | Common function
     */
    public function adjustmentInConnection($request, $newConnectionCharges, $waterApplicationDetails, $applicationCharge)
    {
        $applicationId      = $request->applicationId;
        $refPaymentStatus   = 0;                                                                    // Static
        $newCharge          = $newConnectionCharges['conn_fee_charge']['amount'];

        $mWaterPenaltyInstallment       = new WaterPenaltyInstallment();
        $mWaterConnectionCharge         = new WaterConnectionCharge();
        $mWaterApplication              = new WaterApplication();
        $chargeCatagory                 = Config::get('waterConstaint.CHARGE_CATAGORY');
        $refInstallment['penalty_head'] = Config::get('waterConstaint.PENALTY_HEAD.1');


        # connection charges
        $request->merge([
            'chargeCatagory'    => $chargeCatagory['SITE_INSPECTON'],
            'connectionType'    => $chargeCatagory['SITE_INSPECTON'],
            'ward_id'           => $waterApplicationDetails['ward_id']
        ]);

        switch ($newCharge) {
                # in case of connection charge is not 0
            case ($newCharge != 0):
                # cherge in changes 
                if ($newConnectionCharges['conn_fee_charge']['conn_fee'] > $applicationCharge['conn_fee']) {
                    $adjustedConnFee = $newConnectionCharges['conn_fee_charge']['conn_fee'] - $applicationCharge['conn_fee'];
                    $newConnectionCharges['conn_fee_charge']['conn_fee'] = $adjustedConnFee;
                    $payPenalty = false;

                    # get Water Application Penalty  
                    if ($newConnectionCharges['conn_fee_charge']['penalty'] > $applicationCharge['penalty']) {
                        $unpaidPenalty = $this->checkOldPenalty($applicationId, $chargeCatagory);
                        $calculetedPenalty = $newConnectionCharges['conn_fee_charge']['penalty'] - $applicationCharge['penalty'];
                        $refInstallment['installment_amount'] = $calculetedPenalty + $unpaidPenalty;
                        $refInstallment['balance_amount'] =  $refInstallment['installment_amount'];

                        $mWaterPenaltyInstallment->deactivateOldPenalty($request, $applicationId, $chargeCatagory);
                        $payPenalty = true;
                        # --- if write here penalty  
                    }
                    # for the Case of no extra penalty 
                    $unpaidPenalty = $this->checkOldPenalty($applicationId, $chargeCatagory);
                    if ($unpaidPenalty != 0) {
                        $refInstallment['installment_amount'] = $unpaidPenalty;
                        $refInstallment['balance_amount'] =  $refInstallment['installment_amount'];
                        $newConnectionCharges['conn_fee_charge']['penalty'] = $refInstallment['installment_amount'] ?? 0;

                        $mWaterPenaltyInstallment->deactivateOldPenalty($request, $applicationId, $chargeCatagory);
                        $payPenalty = true;
                        # --- if write here penalty  
                    }
                    # if there is no old penalty and all penalty is paid
                    if ($newConnectionCharges['conn_fee_charge']['penalty'] == 0) {
                        $mWaterPenaltyInstallment->deactivateOldPenalty($request, $applicationId, $chargeCatagory);
                    }
                    $newConnectionCharges['conn_fee_charge']['amount'] = $newConnectionCharges['conn_fee_charge']['penalty'] + $newConnectionCharges['conn_fee_charge']['conn_fee'];
                    $connectionId = $mWaterConnectionCharge->saveWaterCharge($applicationId, $request, $newConnectionCharges);

                    # in case of change or payment of penalty
                    if ($payPenalty == true) {
                        $mWaterPenaltyInstallment->saveWaterPenelty($applicationId, $refInstallment, $chargeCatagory['SITE_INSPECTON'], $connectionId, null);
                    }
                    $mWaterApplication->updatePaymentStatus($applicationId, false);
                    $refPaymentStatus = 0;                                              // Static
                    break;
                }
                # in case of no change in connection charges but the old penalty is unpaid
                $unpaidPenalty = $this->checkOldPenalty($applicationId, $chargeCatagory);
                if ($unpaidPenalty != 0) {
                    $refInstallment['installment_amount'] = $unpaidPenalty;
                    $refInstallment['balance_amount'] =  $unpaidPenalty;
                    $newConnectionCharges['conn_fee_charge']['penalty'] = $unpaidPenalty;

                    $mWaterPenaltyInstallment->deactivateOldPenalty($request, $applicationId, $chargeCatagory);
                    # --- if write here penalty  

                    # Static Connection fee
                    $newConnectionCharges['conn_fee_charge']['conn_fee'] = 0;
                    $newConnectionCharges['conn_fee_charge']['amount'] = $unpaidPenalty;
                    $connectionId = $mWaterConnectionCharge->saveWaterCharge($applicationId, $request, $newConnectionCharges);
                    $mWaterPenaltyInstallment->saveWaterPenelty($applicationId, $refInstallment, $chargeCatagory['SITE_INSPECTON'], $connectionId, null);
                    $mWaterApplication->updatePaymentStatus($applicationId, false);
                    $refPaymentStatus = 0;                                              // Static
                    break;
                }
                $refPaymentStatus = 1;                                                  // Static
                break;
            case ($newCharge == 0):
                $mWaterPenaltyInstallment->deactivateOldPenalty($request, $applicationId, $chargeCatagory);
                $mWaterApplication->updatePaymentStatus($applicationId, true);
                $refPaymentStatus = 1;                                                  // Static
                break;
        }
        return $refPaymentStatus;
    }


    /**
     * | Check for the old penalty 
     * | @param applicationID
     * | @param chargeCatagory
     * | @var mWaterPenaltyInstallment
     * | @var oldPenalty
     * | @var unpaidPenalty
        | Serial No : 04.02.01
        | Working
        | Common function
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
     * | @var user
     * | @var midGeneration
     * | @var mwaterTran
     * | @return 
        | Serial No : 05
        | Working
        | Check for Advance adjustment
     */
    public function offlineDemandPayment(reqDemandPayment $request)
    {
        try {
            $user                       = authUser($request);
            $midGeneration              = new IdGeneration;
            $mWaterAdjustment           = new WaterAdjustment();
            $mwaterTran                 = new waterTran();
            $mWaterConsumerCollection   = new WaterConsumerCollection();

            $offlinePaymentModes    = Config::get('payment-constants.VERIFICATION_PAYMENT_MODE');
            $adjustmentFor          = Config::get("waterConstaint.ADVANCE_FOR");

            $todayDate      = Carbon::now();
            $startingDate   = Carbon::createFromFormat('Y-m-d',  $request->demandFrom)->startOfMonth();
            $endDate        = Carbon::createFromFormat('Y-m-d',  $request->demandUpto)->endOfMonth();
            $startingDate   = $startingDate->toDateString();
            $endDate        = $endDate->toDateString();

            if (!$user->ulb_id) {
                throw new Exception("Ulb Not Found!");
            }
            $finalCharges = $this->preOfflinePaymentParams($request, $startingDate, $endDate);

            DB::beginTransaction();
            $tranNo = $midGeneration->generateTransactionNo($user->ulb_id);
            $request->merge([
                'userId'            => $user->id,
                'userType'          => $user->user_type,
                'todayDate'         => $todayDate->format('Y-m-d'),
                'tranNo'            => $tranNo,
                'id'                => $request->consumerId,
                'ulbId'             => $user->ulb_id,
                'chargeCategory'    => "Demand Collection",                                 // Static
                'leftDemandAmount'  => $finalCharges['leftDemandAmount'],
                'adjustedAmount'    => $finalCharges['adjustedAmount'],
                'isJsk'             => true                                                 // Static
            ]);
            # Save the Details of the transaction
            $wardId['ward_mstr_id'] = collect($finalCharges['consumer'])['ward_mstr_id'];
            $waterTrans = $mwaterTran->waterTransaction($request, $wardId);

            # Save the Details for the Cheque,DD,neft
            if (in_array($request['paymentMode'], $offlinePaymentModes)) {
                $request->merge([
                    'chequeDate'    => $request['chequeDate'],
                    'tranId'        => $waterTrans['id'],
                    'applicationNo' => collect($finalCharges['consumer'])['consumer_no'],
                    'workflowId'    => 0,                                                   // Static
                    'ward_no'       => collect($finalCharges['consumer'])['ward_mstr_id']
                ]);
                $this->postOtherPaymentModes($request);
            }

            # adjustment data saving
            if ($finalCharges['adjustedAmount'] > 0) {
                $mWaterAdjustment->saveAdjustment($waterTrans, $request, $adjustmentFor['1']);
            }
            # Save the fine data in the 
            if ($finalCharges['penaltyAmount'] > 0) {
                $this->savePenaltyDetails($waterTrans, $finalCharges['penaltyAmount']);
            }
            # Reflect on water Tran Details
            $consumercharges = collect($finalCharges['consumerChages']);
            foreach ($consumercharges as $charges) {
                $this->saveConsumerPaymentStatus($request, $offlinePaymentModes, $charges, $waterTrans);
                $mWaterConsumerCollection->saveConsumerCollection($charges, $waterTrans, $user->id);
            }
            DB::commit();
            return responseMsgs(true, "payment Done!", "", "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", $request->deviceId);
        }
    }

    /**
     * | Check the Condition before payment
     * | @param request
     * | @param startingDate
     * | @param endDate
        | Serial No : 05:01
        | Working
        | Common function
        | Check for the rounding of amount 
     */
    public function preOfflinePaymentParams($request, $startingDate, $endDate)
    {
        $mWaterConsumerDemand   = new WaterConsumerDemand();
        $mWaterConsumer         = new WaterConsumer();
        $consumerId             = $request->consumerId;
        $refAmount              = $request->amount;

        if ($startingDate > $endDate) {
            throw new Exception("demandFrom Date should not be grater than demandUpto date!");
        }
        $refConsumer = $mWaterConsumer->getConsumerDetailById($consumerId);
        if (!$refConsumer) {
            throw new Exception("Consumer Not Found!");
        }

        # get charges according to respective from and upto date 
        $allCharges = $mWaterConsumerDemand->getFirstConsumerDemand($consumerId)
            ->where('demand_from', '>=', $startingDate)
            ->where('demand_upto', '<=', $endDate)
            ->get();
        $checkCharges = collect($allCharges)->last();
        if (!$checkCharges->id || is_null($checkCharges)) {
            throw new Exception("Charges for respective date doesn't exist!......");
        }

        # calculation Part
        $refadvanceAmount = $this->checkAdvance($request);
        $totalPaymentAmount = (collect($allCharges)->sum('balance_amount')) - $refadvanceAmount['advanceAmount'];
        if ($totalPaymentAmount != $refAmount) {
            throw new Exception("amount Not Matched!");
        }
        $totalPenalty = collect($allCharges)->sum('penalty');

        # checking the advance amount 
        $allunpaidCharges = $mWaterConsumerDemand->getFirstConsumerDemand($consumerId)
            ->get();
        $leftAmount = (collect($allunpaidCharges)->sum('balance_amount') - collect($allCharges)->sum('balance_amount'));
        return [
            "consumer"          => $refConsumer,
            "consumerChages"    => $allCharges,
            "leftDemandAmount"  => $leftAmount,
            "adjustedAmount"    => $refadvanceAmount['advanceAmount'],
            "penaltyAmount"     => $totalPenalty
        ];
    }


    /**
     * | Save the consumer demand payment status
     * | @param request
     * | @param offlinePaymentModes
     * | @param charges
     * | @param waterTrans
        | Serial No : 05:02
        | Working
        | Common function
     */
    public function saveConsumerPaymentStatus($request, $offlinePaymentModes, $charges, $waterTrans)
    {
        $waterTranDetail    = new WaterTranDetail();
        $mWaterTran         = new WaterTran();

        if (in_array($request['paymentMode'], $offlinePaymentModes)) {
            $charges->paid_status = 2;                                       // Update Demand Paid Status // Static
            $mWaterTran->saveVerifyStatus($waterTrans['id']);
        } else {
            $charges->paid_status = 1;                                      // Update Demand Paid Status // Static
        }
        $charges->save();                                                   // Save Demand
        $waterTranDetail->saveDefaultTrans(
            $charges->amount,
            $request->consumerId ?? $request->applicationId,
            $waterTrans['id'],
            $charges['id'],
        );
    }


    /**
     * | Save the penalty in the fine rebate table 
     * | @param waterTrans
     * | @param penaltyAmount
        | Not tested
        | Serial No : 05:03
        | Common function
     */
    public function savePenaltyDetails($waterTrans, $penaltyAmount)
    {
        $transactionId = $waterTrans['id'];
        $refHeadNames = Config::get("waterConstaint.WATER_HEAD_NAME");
        $mWaterTranFineRebate = new WaterTranFineRebate();
        $metaRequest = new Request([
            'headName'      => $refHeadNames['2'],
            'amount'        => $penaltyAmount,
            'valueAddMinus' => "+",                                                 // Static
            'applicationId' => null                                                 // Static
        ]);
        $mWaterTranFineRebate->saveRebateDetails($metaRequest, $transactionId);
    }


    /**
     * | Calculate the Demand for the respective Consumer
     * | @param request
     * | @var collectiveCharges
     * | @var returnData
        | Working
        | Serial No : 06
        | Check the advance concept
     */
    public function callDemandByMonth(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'consumerId' => 'required',
                'demandFrom' => 'required|date|date_format:Y-m-d',
                'demandUpto' => 'required|date|date_format:Y-m-d',
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $startingDate   = Carbon::createFromFormat('Y-m-d',  $request->demandFrom)->startOfMonth();
            $endDate        = Carbon::createFromFormat('Y-m-d',  $request->demandUpto)->endOfMonth();
            $startingDate   = $startingDate->toDateString();
            $endDate        = $endDate->toDateString();

            $collectiveCharges = $this->checkCallParams($request, $startingDate, $endDate);

            # when advance is collected
            # update the penalty used status after its use
            $refadvanceAmount = $this->checkAdvance($request);
            $advanceAmount = $refadvanceAmount['advanceAmount'];
            $totalPaymentAmount  = collect($collectiveCharges)->pluck('balance_amount')->sum();
            $actualCallAmount = $totalPaymentAmount - $advanceAmount;
            if ($actualCallAmount < 0) {
                $totalPayAmount = 0;                                                                // Static
                $renmaningAmount = $actualCallAmount * -1;
            } else {
                $totalPayAmount = $actualCallAmount;
                $renmaningAmount = 0;                                                               // Static
            }

            $returnData = [
                'totalPayAmount'        => $totalPayAmount,
                'totalPenalty'          => collect($collectiveCharges)->pluck('penalty')->sum(),
                'totalDemand'           => collect($collectiveCharges)->pluck('amount')->sum(),
                'totalAdvance'          => $advanceAmount,
                'totalRebate'           => 0,                                                       // Static
                'remaningAdvanceAmount' => $renmaningAmount
            ];
            return responseMsgs(true, "Amount Details!", remove_null($returnData), "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", $request->deviceId);
        }
    }

    /**
     * | calling functon for checking params for callculating demand according to month
     * | @param request
     * | @var consumerDetails
     * | @var mWaterConsumerDemand
     * | @var allCharges
     * | @var checkDemand
     * | @return allCharges
        | Serial No : 06.01
        | Working  
        | Common function
     */
    public function checkCallParams($request, $startingDate, $endDate)
    {
        $mWaterConsumerDemand = new WaterConsumerDemand();
        if ($startingDate > $endDate) {
            throw new Exception("'demandFrom' date should not be grater than 'demandUpto' date!");
        }
        $consumerDetails = WaterConsumer::find($request->consumerId);
        if (!$consumerDetails) {
            throw new Exception("Consumer dont exist!");
        }

        # get demand by (upto and from) date 
        $allCharges = $mWaterConsumerDemand->getFirstConsumerDemand($request->consumerId)
            ->where('demand_from', '>=', $startingDate)
            ->where('demand_upto', '<=', $endDate)
            ->get();
        $checkDemand = collect($allCharges)->first();
        if (!$checkDemand || is_null($checkDemand)) {
            throw new Exception("Demand according to given date not found!");
        }
        return $allCharges;
    }


    /**
     * | Check the Advance and its existence
     * | Advance and Adjustment calcullation 
     * | @param request contain consumerId
        | Serial No : 06:02 / 05:01:01
        | Not Working
        | Common function
     */
    public function checkAdvance($request)
    {
        $mWaterAdvance      = new WaterAdvance();
        $mWaterAdjustment   = new WaterAdjustment();
        $refAdvanceFor      = Config::get("waterConstaint.ADVANCE_FOR");
        $consumerId         = $request->consumerId;
        $advanceFor         = $refAdvanceFor['1'];

        $refAdvance = $mWaterAdvance->getAdvanceByRespectiveId($consumerId, $advanceFor)->get();
        $refAdjustment = $mWaterAdjustment->getAdjustedDetails($consumerId)->get();

        $totalAdvance = (collect($refAdvance)->pluck("amount")->sum()) ?? 0;
        $totalAdjustment = (collect($refAdjustment)->pluck("amount")->sum()) ?? 0;
        $advanceAmount = $totalAdvance - $totalAdjustment;
        if ($advanceAmount <= 0) {
            $advanceAmount = 0;                                                                     // Static
        }

        return $returnData = [
            "totaladvanceAmount"    => $totalAdvance,
            "advanceAmount"         => $advanceAmount,
            "adjustedAmount"        => $totalAdjustment
        ];
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
            $user       = authUser($req);
            $userId     = $user->id;
            $userType   = $user->user_type;
            $todayDate  = Carbon::now();

            $waterTran              = new WaterTran();
            $idGeneration           = new IdGeneration;
            $mWaterApplication      = new WaterApplication();
            $mWaterConnectionCharge = new WaterConnectionCharge();
            $mWaterSiteInspection   = new WaterSiteInspection();

            $offlinePaymentModes = Config::get('payment-constants.VERIFICATION_PAYMENT_MODE');
            $paramChargeCatagory = Config::get('waterConstaint.CHARGE_CATAGORY');                                                      # Authenticated user or Ghost User
            $refWaterApplication = $mWaterApplication->getApplicationById($req->applicationId)
                ->firstOrFail();

            # check the pre requirement 
            $refCharges = $this->verifyPaymentRules($req, $refWaterApplication);

            # Derivative Assignments
            if (!$user->ulb_id) {
                throw new Exception("Ulb Not Found!");
            }
            $tranNo = $idGeneration->generateTransactionNo($user->ulb_id);
            $charges = $mWaterConnectionCharge->getWaterchargesById($req->applicationId)
                ->where('paid_status', 0)
                ->get();                                                                                        # get water User connectin charges

            if (!$charges || collect($charges)->isEmpty()) {
                $this->checkForCharges($req);
            }
            # Water Transactions
            $req->merge([
                'userId'        => $userId,
                'userType'      => $userType,
                'todayDate'     => $todayDate->format('Y-m-d'),
                'tranNo'        => $tranNo,
                'id'            => $req->applicationId,
                'ulbId'         => $user->ulb_id,
                'isJsk'         => true,                                                                // Static
                'penaltyIds'    => $refCharges['penaltyIds'] ?? null,
                'isPenalty'     => $refCharges['isPenalty']
            ]);

            DB::beginTransaction();
            # Save the Details of the transaction
            $wardId['ward_mstr_id'] = $refWaterApplication['ward_id'];
            $waterTrans = $waterTran->waterTransaction($req, $wardId);

            # Save rebate details in table in case of 10% penalty rebate
            if ($req->chargeCategory == $paramChargeCatagory['REGULAIZATION'] && $req->isInstallment == "no") {
                $this->saveRebateForTran($req, $charges, $waterTrans);
            }

            # Save the Details for the Cheque,DD,nfet
            if (in_array($req['paymentMode'], $offlinePaymentModes)) {
                $req->merge([
                    'chequeDate'    => $req['chequeDate'],
                    'tranId'        => $waterTrans['id'],
                    'applicationNo' => $refWaterApplication['application_no'],
                    'workflowId'    => $refWaterApplication['workflow_id'],
                    'ward_no'       => $refWaterApplication['ward_id']
                ]);
                $this->postOtherPaymentModes($req);
            }

            # Reflect on water Tran Details and for Applications Data saving
            if (!(collect($charges)->isEmpty())) {
                foreach ($charges as $charges) {
                    $this->savePaymentStatus($req, $offlinePaymentModes, $charges, $refWaterApplication, $waterTrans);
                }
            } else {
                $this->saveRegulaizePaymentStatus($req, $offlinePaymentModes, $waterTrans);
            }
            # Readjust Water Penalties 
            $this->updatePenaltyPaymentStatus($req);
            # if payment is for site inspection
            if ($req->chargeCategory == $paramChargeCatagory['SITE_INSPECTON']) {
                $mWaterSiteInspection->saveSitePaymentStatus($req->applicationId);
            }
            DB::commit();
            return responseMsgs(true, "Payment Successfully Done",  ['TransactionNo' => $tranNo], "", "1.0", "ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * | Save the regulization second payament details 
     * | if the payment for the regularization is done in installment
        | Serial No : 07:06
     */
    public function saveRegulaizePaymentStatus($req, $offlinePaymentModes, $waterTrans)
    {
        $mWaterTran                 = new WaterTran();
        $mWaterPenaltyInstallment   = new WaterPenaltyInstallment();

        $penatydetails = $mWaterPenaltyInstallment->getPenaltyByArrayOfId($req->penaltyIds);
        $checkPenalty = collect($penatydetails)->first();
        if (!$checkPenalty) {
            throw new Exception("penaty Not found for REGULAIZATION!");
        }

        # for offline payment mode 
        $penaltyIds = collect($penatydetails)->pluck('id');
        if (in_array($req['paymentMode'], $offlinePaymentModes)) {
            $penaltyStatus = 2;                                                          // Static
            $mWaterTran->saveVerifyStatus($waterTrans['id']);
        } else {
            $penaltyStatus = 1;                                                          // Update Demand Paid Status // Static
        }
        $penaltyIds = implode(",", $req->penaltyIds);
        $mWaterTran->saveIsPenalty($waterTrans['id'], $penaltyIds);
        $mWaterPenaltyInstallment->savePenaltyStatusByIds($penaltyIds, $penaltyStatus);
    }


    /**
     * | Check for the payment done 
     * | @param req
        | Check if the charges id not present 
        | Serial No : 07:05
     */
    public function checkForCharges($req)
    {
        $mWaterPenaltyInstallment = new WaterPenaltyInstallment();
        $paramChargeCatagory = Config::get('waterConstaint.CHARGE_CATAGORY');

        if ($req->chargeCategory == $paramChargeCatagory['REGULAIZATION']) {
            $penaltyDetails = $mWaterPenaltyInstallment->getPenaltyByApplicationId($req->applicationId)->get();
            $checkPenalty = collect($penaltyDetails)->first();
            if (!$checkPenalty) {
                throw new Exception("Connection Not Available for Payment!");
            }
            $checkPenaltyPayment = collect($penaltyDetails)->pluck('paid_status');
            $containsOnlyOnes = $checkPenaltyPayment->every(function ($value) {
                return $value === 1;
            });
            if ($containsOnlyOnes) {
                throw new Exception("All Payment is done for penalty as well!");
            }
        }
    }


    /**
     * | Save the payment status for respective payment
     * | @param req
     * | @param offlinePaymentModes
     * | @param charges
     * | @param refWaterApplication
     * | @param waterTrans
        | Serial No : 07.04
        | Working
        | Common function
        | $charges is for the water charges 
        | Write the code to send data in the track table (1146,1149)
     */
    public function savePaymentStatus($req, $offlinePaymentModes, $charges, $refWaterApplication, $waterTrans)
    {
        $mWaterApplication      = new WaterApplication();
        $waterTranDetail        = new WaterTranDetail();
        $mWaterTran             = new WaterTran();
        $refRole                = Config::get("waterConstaint.ROLE-LABEL");
        $applyFrom              = Config::get('waterConstaint.APP_APPLY_FROM');
        $paramChargeCatagory    = Config::get('waterConstaint.CHARGE_CATAGORY');

        # for offline payment mode 
        if (in_array($req['paymentMode'], $offlinePaymentModes)) {
            $charges->paid_status = 2;                                                          // Static
            $mWaterApplication->updatePendingStatus($req['id']);
            $mWaterTran->saveVerifyStatus($waterTrans['id']);
        } else {
            $charges->paid_status = 1;                                                          // Update Demand Paid Status // Static
            if ($refWaterApplication['payment_status'] == 0) {                                  // Update Water Application Payment Status
                $mWaterApplication->updateOnlyPaymentstatus($req['id']);                        // If payment is in cash
            }
        }

        # saving Details in application table if payment is in JSK
        if ($req->chargeCategory != $paramChargeCatagory['SITE_INSPECTON']) {
            if ($refWaterApplication->apply_from == $applyFrom['1'] && $refWaterApplication->doc_upload_status == true) {
                # write code for track table
                $mWaterApplication->sendApplicationToRole($req['id'], $refRole['DA']);                // Save current role as Da
            } else {
                # write code for track table
                $mWaterApplication->sendApplicationToRole($req['id'], $refRole['BO']);                // Save current role as Bo
            }
        }
        $charges->save();

        # Save the trans details 
        $waterTranDetail->saveDefaultTrans(
            $charges->conn_fee,
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
        | Common function
        | Not tested
     */
    public function verifyPaymentRules($req, $refApplication)
    {
        $mWaterPenaltyInstallment   = new WaterPenaltyInstallment();
        $mWaterConnectionCharge     = new WaterConnectionCharge();
        $paramChargeCatagory        = Config::get('waterConstaint.CHARGE_CATAGORY');
        $connectionTypeIdConfig     = Config::get('waterConstaint.CONNECTION_TYPE');
        $isPenalty                  = null;

        switch ($req) {
                # In Case of Residential payment Offline
            case ($req->chargeCategory == $paramChargeCatagory['REGULAIZATION']):
                if ($refApplication['connection_type_id'] != $connectionTypeIdConfig['REGULAIZATION']) {
                    throw new Exception("The respective application in not for Regulaization!");
                }
                switch ($req) {
                    case ($req->isInstallment == "yes"):
                        $penaltyIds         = $req->penaltyIds;
                        $returnPenaltyIds   = implode(",", $penaltyIds);
                        $refPenallty        = $mWaterPenaltyInstallment->getPenaltyByArrayOfId($penaltyIds);
                        $isPenalty          = 0;

                        $checkPenalty = collect($refPenallty)->first();
                        if (is_null($checkPenalty)) {
                            throw new Exception("Penalty Details not found!");
                        }
                        collect($refPenallty)->map(function ($value) {
                            if ($value['paid_status'] == 1) {
                                throw new Exception("payment for the respoctive Penaty has been done!");
                            }
                        });
                        $refPenaltySumAmount = collect($refPenallty)->map(function ($value) {
                            return $value['balance_amount'];
                        })->sum();

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
                        if ($refPenaltySumAmount != ($req->amount - ($refAmount ?? 0))) {
                            throw new Exception("Respective Penalty Amount Not Matched!");
                        }

                        break;
                    case ($req->isInstallment == "no"): # check <-------------- calculation 
                        $actualCharge = $mWaterConnectionCharge->getWaterchargesById($req->applicationId)
                            ->where('charge_category', $req->chargeCategory)
                            ->firstOrFail();

                        $refPenallty = $mWaterPenaltyInstallment->getPenaltyByApplicationId($req->applicationId)->get();
                        collect($refPenallty)->map(function ($value) {
                            if ($value['paid_status'] == 1) {
                                throw new Exception("payment for respective Penaty has been done!");
                            }
                        });

                        $actualPenaltyAmountRebate = (10 / 100 * $actualCharge['penalty']);
                        $callAmount = $actualCharge['amount'] - $actualPenaltyAmountRebate;
                        if ($req->amount != $callAmount) {
                            throw new Exception("Connection amount Not Matched!");
                        }

                        $actualPenalty = collect($refPenallty)->sum('balance_amount');
                        if ($req->penaltyAmount != $actualPenalty) {
                            throw new Exception("provided Penalty not matched!");
                        }

                        $chargeAmount =  $req->amount - ($req->penaltyAmount - $actualPenaltyAmountRebate);
                        if ($actualCharge['conn_fee'] != $chargeAmount) {
                            throw new Exception("Connection fee not matched!");
                        }

                        $refPenaltyIds = collect($refPenallty)->pluck("id");
                        $returnPenaltyIds = implode(',', $refPenaltyIds->toArray());
                        $isPenalty = 0;
                        # save penalty and rebate details 
                        # write code to track the rebate and the penalty
                        break;
                }
                break;

                # In Case of New Connection payment Offline
            case ($req->chargeCategory == $paramChargeCatagory['NEW_CONNECTION']):
                if ($refApplication['connection_type_id'] != $connectionTypeIdConfig['NEW_CONNECTION']) {
                    throw new Exception("The respective application in not for New Connection!");
                }
                switch ($req) {
                    case (is_null($req->isInstallment) || !$req->isInstallment || $req->isInstallment == "no"):
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
                    ->orderByDesc('id')
                    ->firstOrFail();
                if ($actualCharge['amount'] != $req->amount) {
                    throw new Exception("Amount Not Matched!");
                }
                if ($req->isInstallment == "yes") {
                    throw new Exception("No Installment in Site Inspection Charges!");
                }
                break;
        }
        return [
            'charges'       => $actualCharge,
            'penaltyIds'    => $returnPenaltyIds ?? null,
            'isPenalty'     => $isPenalty
        ];
    }


    /**
     * | Post Other Payment Modes for Cheque,DD,Neft
     * | @param req
        | Serial No : 07.02
        | Working
        | Common function
     */
    public function postOtherPaymentModes($req)
    {
        $cash               = Config::get('payment-constants.PAYMENT_MODE.3');
        $moduleId           = Config::get('module-constants.WATER_MODULE_ID');
        $mTempTransaction   = new TempTransaction();
        $mPropChequeDtl     = new WaterChequeDtl();

        if ($req['paymentMode'] != $cash) {
            if ($req->chargeCategory == "Demand Collection") {
                $chequeReqs = [
                    'user_id'           => $req['userId'],
                    'consumer_id'       => $req['id'],
                    'transaction_id'    => $req['tranId'],
                    'cheque_date'       => $req['chequeDate'],
                    'bank_name'         => $req['bankName'],
                    'branch_name'       => $req['branchName'],
                    'cheque_no'         => $req['chequeNo']
                ];
            } else {
                $chequeReqs = [
                    'user_id'           => $req['userId'],
                    'application_id'    => $req['id'],
                    'transaction_id'    => $req['tranId'],
                    'cheque_date'       => $req['chequeDate'],
                    'bank_name'         => $req['bankName'],
                    'branch_name'       => $req['branchName'],
                    'cheque_no'         => $req['chequeNo']
                ];
            }
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
            'ward_no'           => $req['ward_no']
        ];
        $mTempTransaction->tempTransaction($tranReqs);
    }

    /**
     * | Update the penalty Status 
     * | @param req
     * | @var mWaterPenaltyInstallment
        | Serial No : 07.03
        | Common function
        | Not tested
     */
    public function updatePenaltyPaymentStatus($req)
    {
        $mWaterPenaltyInstallment = new WaterPenaltyInstallment();
        switch ($req) {
            case (!empty($req->penaltyIds)):
                if (!is_array($req->penaltyIds)) {
                    $arrayPenalty = explode(",", $req->penaltyIds);
                    $req->merge([
                        "penaltyIds" => $arrayPenalty
                    ]);
                }
                $mWaterPenaltyInstallment->updatePenaltyPayment($req->penaltyIds);
                break;

            case (is_null($req->penaltyIds) || empty($req->penaltyIds)):
                $mWaterPenaltyInstallment->getPenaltyByApplicationId($req->applicationId)
                    ->update([
                        'paid_status' => 1,                                                 // Static
                    ]);
                break;
        }
    }


    /**
     * | Get the payment history for the Application
     * | @param request
        | Serial No : 08
        | Working
     */
    public function getApplicationPaymentHistory(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'id' => 'required|digits_between:1,9223372036854775807'
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $mWaterTran                 = new WaterTran();
            $mWaterApplication          = new WaterApplication();
            $mWaterConnectionCharge     = new WaterConnectionCharge();
            $mWaterPenaltyInstallment   = new WaterPenaltyInstallment();

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


    /**
     * | Generate Demand Payment receipt
     * | @param req
        | Serial No : 09
        | Working
     */
    public function generateDemandPaymentReceipt(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'transactionNo' => 'required'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $refTransactionNo       = $req->transactionNo;
            $mWaterConsumerDemand   = new WaterConsumerDemand();
            $mWaterConsumer         = new WaterConsumer();
            $mWaterTranDetail       = new WaterTranDetail();
            $mWaterChequeDtl        = new WaterChequeDtl();
            $mWaterTran             = new WaterTran();
            $mWaterConsumerMeter    = new WaterConsumerMeter();
            $mWaterConsumerTax      = new WaterConsumerTax();

            $mTowardsDemand     = Config::get("waterConstaint.TOWARDS_DEMAND");
            $mTranType          = Config::get("waterConstaint.PAYMENT_FOR");
            $mAccDescription    = $this->_accDescription;
            $mDepartmentSection = $this->_departmentSection;
            $mPaymentModes      = $this->_paymentModes;

            # transaction Deatils
            $transactionDetails = $mWaterTran->getTransactionByTransactionNo($refTransactionNo)
                ->where('tran_type', $mTranType['1'])
                ->firstOrFail();

            #  Data not equal to Cash
            if (!in_array($transactionDetails['payment_mode'], [$mPaymentModes['1'], $mPaymentModes['5']])) {
                $chequeDetails = $mWaterChequeDtl->getChequeDtlsByTransId($transactionDetails['id'])->first();
            }
            # Application Deatils
            $consumerDetails = $mWaterConsumer->getRefDetailByConsumerNo("id", $transactionDetails->related_id)->firstOrFail();

            # consumer Demand Details 
            $detailsOfDemand = $mWaterTranDetail->getTransDemandByIds($transactionDetails->id)->get();

            # Connection Charges
            $demandIds = collect($detailsOfDemand)->pluck('demand_id');
            $consumerDemands = $mWaterConsumerDemand->getDemandCollectively($demandIds)
                ->orderBy('demand_from')
                ->get();
            $taxids = collect($consumerDemands)->pluck('consumer_tax_id')->filter();
            if (!empty($taxids) || !is_null($taxids)) {
                $meterReadings = $mWaterConsumerTax->getTaxById($taxids)
                    ->orderByDesc('id')
                    ->get();
                $lastDemand = collect($meterReadings)->first()['initial_reading'];
                $currentDemand = collect($meterReadings)->first()['final_reading'];
            }

            $fromDate           = collect($consumerDemands)->first()->demand_from;
            $startingDate       = Carbon::createFromFormat('Y-m-d',  $fromDate)->startOfMonth();
            $uptoDate           = collect($consumerDemands)->last()->demand_upto;
            $endingDate         = Carbon::createFromFormat('Y-m-d',  $uptoDate)->endOfMonth();
            $penaltyAmount      = collect($consumerDemands)->sum('penalty');
            $refDemandAmount    = collect($consumerDemands)->sum('balance_amount');

            # consumer meter details 
            $consumerMeterDetails = $mWaterConsumerMeter->getMeterDetailsByConsumerId($consumerDetails->id)
                ->first();

            # water consumer consumed
            $consumerTaxes = $mWaterConsumerDemand->getConsumerTax($demandIds);
            $initialReading = $consumerTaxes->wherein("connection_type", ["Meter", "Metered"])  // Static
                ->min("initial_reading");
            $finalReading = $consumerTaxes->wherein("connection_type", ["Meter", "Metered"])    // Static
                ->max("final_reading");
            $fixedFrom = $consumerTaxes->where("connection_type", "Fixed")                      // Static
                ->min("demand_from");
            $fixedUpto = $consumerTaxes->where("connection_type", "Fixed")                      // Static
                ->max("demand_upto");

            $returnValues = [
                "departmentSection"     => $mDepartmentSection,
                "accountDescription"    => $mAccDescription,
                "transactionDate"       => $transactionDetails['tran_date'],
                "transactionNo"         => $refTransactionNo,
                "consumerNo"            => $consumerDetails['consumer_no'],
                "customerName"          => $consumerDetails['applicant_name'],
                "customerMobile"        => $consumerDetails['mobile_no'],
                "address"               => $consumerDetails['address'],
                "paidFrom"              => $startingDate->format('Y-m-d'),
                "paidUpto"              => $endingDate->format('Y-m-d'),
                "holdingNo"             => $consumerDetails['holding_no'],
                "safNo"                 => $consumerDetails['saf_no'],
                "paymentMode"           => $transactionDetails['payment_mode'],
                "bankName"              => $chequeDetails['bank_name']   ?? null,                                    // in case of cheque,dd,nfts
                "branchName"            => $chequeDetails['branch_name'] ?? null,                                  // in case of chque,dd,nfts
                "chequeNo"              => $chequeDetails['cheque_no']   ?? null,                                   // in case of chque,dd,nfts
                "chequeDate"            => $chequeDetails['cheque_date'] ?? null,                                  // in case of chque,dd,nfts
                "penaltyAmount"         => $penaltyAmount,
                "demandAmount"          => $refDemandAmount,
                "ulbId"                 => $consumerDetails['ulb_id'],
                "ulbName"               => $consumerDetails['ulb_name'],
                "WardNo"                => $consumerDetails['ward_name'],
                "logo"                  => $consumerDetails['logo'],
                "towards"               => $mTowardsDemand,
                "description"           => $mAccDescription,
                "totalPaidAmount"       => $transactionDetails->amount,
                "dueAmount"             => $transactionDetails->due_amount,
                "rebate"                => 0,                                                                       // Static
                "meterNo"               => $consumerMeterDetails->meter_no ?? null,
                "waterConsumed"         => (($finalReading ?? 0.00) - ($initialReading ?? 0.00)),
                "initialReding"         => $initialReading ?? null,
                "finalReading"          => $finalReading ?? null,
                "fixedPaidFrom"         => ($fixedFrom) ? Carbon::createFromFormat('Y-m-d',  $fixedFrom)->startOfMonth() : null,
                "fixedPaidUpto"         => ($fixedUpto) ? (Carbon::createFromFormat('Y-m-d',  $fixedUpto)->endOfMonth()) : null,
                "lastMeterReadingDate"    => $fromDate ?? null,
                "currentMeterReadingDate" => $uptoDate ?? null,
                "lastMeterReading"      => $lastDemand ?? null,
                "currentMeterReading"   => $currentDemand ?? null,
                "paidAmtInWords"        => getIndianCurrency($transactionDetails->amount),

            ];
            return responseMsgs(true, "Payment Receipt", remove_null($returnValues), "", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
        }
    }


    /**
     * | Initiate the online Demand payment Online
     * | Get the details for order id 
     * | Check the amount for the orderId
     * | @param request
        | Working
        | Serial No : 10
        | Collect the error occure while order id is generated
     */
    public function initiateOnlineDemandPayment(reqDemandPayment $request)
    {
        try {
            $refUser        = authUser($request);
            $waterModuleId  = Config::get('module-constants.WATER_MODULE_ID');
            $paymentFor     = Config::get('waterConstaint.PAYMENT_FOR');
            $startingDate   = Carbon::createFromFormat('Y-m-d',  $request->demandFrom)->startOfMonth();
            $endDate        = Carbon::createFromFormat('Y-m-d',  $request->demandUpto)->endOfMonth();
            $startingDate   = $startingDate->toDateString();
            $endDate        = $endDate->toDateString();
            // $url            = Config::get('razorpay.PAYMENT_GATEWAY_URL');
            // $endPoint       = Config::get('razorpay.PAYMENT_GATEWAY_END_POINT');

            # Demand Collection 
            DB::beginTransaction();
            $refDetails = $this->preOfflinePaymentParams($request, $startingDate, $endDate);
            $myRequest = new Request([
                'amount'        => $request->amount,
                'workflowId'    => 0,                                                                   // Static
                'id'            => $request->consumerId,
                'departmentId'  => $waterModuleId,
                'ulbId'         => $refDetails['consumer']['ulb_id'],
                'auth'          => $refUser
            ]);
            $temp = $this->saveGenerateOrderid($myRequest);
            // $temp = Http::withHeaders([])
            //     ->post($url . $endPoint, $myRequest);                                                   // Static

            // $temp = $temp['data'];
            $mWaterRazorPayRequest = new WaterRazorPayRequest();
            $mWaterRazorPayRequest->saveRequestData($request, $paymentFor['1'], $temp, $refDetails);
            DB::commit();

            $temp['name']   = $refUser->user_name;
            $temp['mobile'] = $refUser->mobile;
            $temp['email']  = $refUser->email;
            $temp['userId'] = $refUser->id;
            $temp['ulbId']  = $refUser->ulb_id;
            return responseMsgs(true, "", $temp, "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "03", ".ms", "POST", $request->deviceId);
        }
    }

    /**
     * | Online Payment for the consumer Demand
     * | Data After the Webhook Payment / Called by the Webhook
     * | @param webhookData
     * | @param RazorPayRequest
        | Serial No : 11
        | Recheck / Not Working
        | Clear the concept
        | Save the pgId and pgResponseId in trans table 
        | Called function 
     */
    public function endOnlineDemandPayment($webhookData, $RazorPayRequest)
    {
        try {
            # ref var assigning
            $today          = Carbon::now();
            $refUserId      = $webhookData["userId"];
            $refUlbId       = $webhookData["ulbId"];
            $mDemands       = (array)null;

            # model assigning
            $mWaterTran                 = new WaterTran();
            $mWaterTranDetail           = new WaterTranDetail();
            $mWaterConsumerDemand       = new WaterConsumerDemand();
            $mWaterRazorPayResponse     = new WaterRazorPayResponse();
            $mWaterConsumerCollection   = new WaterConsumerCollection();
            $mWaterAdjustment           = new WaterAdjustment();

            # variable assigning
            $adjustmentFor = Config::get("waterConstaint.ADVANCE_FOR");
            $consumerDetails = WaterConsumer::find($webhookData["id"]);
            $consumerId = $webhookData["id"];
            $refDate = explode("--", $RazorPayRequest->demand_from_upto);
            $startingYear = $refDate[0];
            $endYear = $refDate[1];

            # calcullate demand
            $mDemands = $mWaterConsumerDemand->checkConsumerDemand($consumerId)
                ->where('demand_from', '>=', $startingYear)
                ->where('demand_upto', '<=', $endYear)
                ->get();
            if (!$RazorPayRequest || round($webhookData['amount']) != round($RazorPayRequest['amount'])) {
                throw new Exception("Payble Amount Missmatch!!!");
            }

            DB::beginTransaction();
            # save payment data in razorpay response table
            $paymentResponseId = $mWaterRazorPayResponse->savePaymentResponse($RazorPayRequest, $webhookData);

            # save the razorpay request status as 1
            $RazorPayRequest->status = 1;                                       // Static
            $RazorPayRequest->update();

            # save data in water transaction table 
            $metaRequest = [
                "id"                => $webhookData["id"],
                'amount'            => $webhookData['amount'],
                'chargeCategory'    => $RazorPayRequest->payment_from,
                'todayDate'         => $today,
                'tranNo'            => $webhookData["transactionNo"],
                'paymentMode'       => "Online",                                // Static
                'citizenId'         => $refUserId,
                'userType'          => "Citizen" ?? null,                       // Check here // Static
                'ulbId'             => $refUlbId,
                'leftDemandAmount'  => $RazorPayRequest->due_amount,
                'adjustedAmount'    => $RazorPayRequest->adjusted_amount,
                'pgResponseId'      => $paymentResponseId['razorpayResponseId'],
                'pgId'              => $webhookData['gatewayType']
            ];
            $consumer['ward_mstr_id'] = $consumerDetails->ward_mstr_id;
            $transactionId = $mWaterTran->waterTransaction($metaRequest, $consumer);

            # adjustment data saving
            $refMetaReq = new Request([
                "consumerId"    => $webhookData['id'],
                "amount"        => $RazorPayRequest['adjusted_amount'],
                "userId"        => $refUserId,
                "remarks"       => "online payment",                            // Static
            ]);
            if ($RazorPayRequest['adjusted_amount'] > 0) {
                $mWaterAdjustment->saveAdjustment($transactionId, $refMetaReq, $adjustmentFor['1']);
            }
            # Save the fine data in the 
            if ($RazorPayRequest['penalty_amount'] > 0) {
                $this->savePenaltyDetails($transactionId, $RazorPayRequest['penalty_amount']);
            }
            foreach ($mDemands as $demand) {
                # save Water trans details 
                $mWaterTranDetail->saveDefaultTrans($demand->amount, $demand->consumer_id, $transactionId['id'], $demand->id);
                $mWaterConsumerCollection->saveConsumerCollection($demand, $transactionId, $refUserId);
                # update the payment status of the demand 
                $demand->paid_status = 1;                                          // Static
                $demand->update();
            }
            DB::commit();
            $res['transactionId'] = $transactionId['id'];
            return responseMsg(true, "", $res);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $webhookData);
        }
    }


    /**
     * | Get citizen payment history to show 
     * | Using user Id for displaying data
     * | @param request 
        | Selail No : 12
        | Use 
        | Show the payment from jsk also
     */
    public function paymentHistory(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'pages' => 'required|int'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $citizen        = authUser($request);
            $citizenId      = $citizen->id;
            $mWaterTran     = new WaterTran();
            $refUserType    = Config::get("waterConstaint.USER_TYPE");

            if ($citizen->user_type != $refUserType["Citizen"]) {
                throw new Exception("You're user type is not citizen!");
            }
            $transactionDetails = $mWaterTran->getTransByCitizenId($citizenId)
                ->select(
                    DB::raw('
                        CASE
                            WHEN tran_type = \'Demand Collection\' THEN \'1\'
                            ELSE \'2\'
                        END AS tran_type_id
                    '),
                    "water_trans.*"
                )->paginate($request->pages);
            return responseMsgs(true, "List of transactions", remove_null($transactionDetails), "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $request->deviceId);
        }
    }


    /**
     * | Get water user charges / Bill
     * | A receipt generated in the time of demand generation 
        | Serial No : 
        | Finished
        | Use
     */
    public function getWaterUserCharges(Request $request)
    {

        $validated = Validator::make(
            $request->all(),
            [
                'consumerNo' => 'required'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $mWaterConsumer         = new WaterConsumer();
            $mWaterConsumerDemand   = new WaterConsumerDemand();

            $refconsumerTowards   = $this->_consumerTowards;
            $refAccDescription    = $this->_accDescription;
            $refDepartmentSection = $this->_departmentSection;

            $refRequest     = $request->toArray();
            $flipRequest    = collect($refRequest)->flip();
            $key            = $flipRequest[$request->consumerNo];
            $string         = preg_replace("/([A-Z])/", "_$1", $key);
            $refstring      = strtolower($string);

            $consumerDetails = $mWaterConsumer->getRefDetailByConsumerNo($refstring, $request->consumerNo)->first();
            if (!$consumerDetails) {
                throw new Exception("Consumer details not found!");
            }

            # Demand Details 
            $refConsumerDemand  = $mWaterConsumerDemand->getConsumerDemand($consumerDetails->id);
            $lastestDemand      = collect($refConsumerDemand)->first();
            $startingDemand     = collect($refConsumerDemand)->last();
            $totalDemandAmount  = round((collect($refConsumerDemand)->sum('balance_amount')), 2);
            $totalPenaltyAmount = round((collect($refConsumerDemand)->sum('penalty')), 2);

            $refRequest = new Request([
                "consumerId" => $consumerDetails->id
            ]);
            # Advance Details 
            $advanceDetails = $this->checkAdvance($refRequest);

            $returnValues = [
                "departmentSection"     => $refDepartmentSection,
                "accountDescription"    => $refAccDescription,
                "consumerNo"            => $consumerDetails['consumer_no'],
                "customerName"          => $consumerDetails['applicant_name'],
                "customerMobile"        => $consumerDetails['mobile_no'],
                "address"               => $consumerDetails['address'],
                "paidFrom"              => ($startingDemand->demand_from) ?? null,
                "paidUpto"              => ($lastestDemand->demand_upto) ?? null,
                "holdingNo"             => $consumerDetails['holding_no'],
                "safNo"                 => $consumerDetails['saf_no'],                                 // in case of chque,dd,nfts
                "penaltyAmount"         => $totalPenaltyAmount ?? 0,
                "billAmount"            => $totalDemandAmount ?? 0,
                "ulbId"                 => $consumerDetails['ulb_id'],
                "ulbName"               => $consumerDetails['ulb_name'],
                "WardNo"                => $consumerDetails['ward_name'],
                "logo"                  => $consumerDetails['logo'],
                "towards"               => $refconsumerTowards,
                "description"           => $refAccDescription,
                "paidAmtInWords"        => getIndianCurrency($totalDemandAmount),
                "billNumber"            => $lastestDemand->demand_no ?? null,
                "advanceAmount"         => $advanceDetails['advanceAmount'] ?? 0,

            ];
            return responseMsgs(true, "water bill details!", remove_null($returnValues), "", "01", "", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $request->deviceId);
        }
    }


    /**
     * | Initiate the online payment for the consumer req for ferrule, pipe shifting 
        | Serial No :
        | Working
     */
    public function initiateOnlineConRequestPayment(reqConsumerReqPayment $request)
    {
        try {
            $refUser        = authUser($request);
            $waterModuleId  = Config::get('module-constants.WATER_MODULE_ID');
            $paymentFor     = Config::get('waterConstaint.PAYMENT_FOR');

            # Pre condition check
            $refDetails = $this->preConsumerPaymentReq($request);

            DB::beginTransaction();
            $myRequest = new Request([
                'amount'        => $refDetails['totalAmount'],
                'workflowId'    => $refDetails['ulbWorkflowId'],                                                                   // Static
                'id'            => $request->applicationId,
                'departmentId'  => $waterModuleId,
                'ulbId'         => $refDetails['ulbId'],
                'auth'          => $refUser
            ]);
            $temp = $this->saveGenerateOrderid($myRequest);
            $mWaterRazorPayRequest = new WaterRazorPayRequest();
            $mWaterRazorPayRequest->saveRequestData($request, $paymentFor[$refDetails['chargeCatagoryId']], $temp, $refDetails);
            DB::commit();
            # Return Details 
            $temp['name']       = $refUser->user_name;
            $temp['mobile']     = $refUser->mobile;
            $temp['email']      = $refUser->email;
            $temp['userId']     = $refUser->id;
            return responseMsgs(true, "Order Id generation succefully!", remove_null($temp), "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /**
     * | Check the param for the payment of the Consumer Requests 
     * | Check for pipe shifting , ferrule cleaning, disconnection
        | Serial No :
        | Working
        | Check the water tranaction data exist
     */
    public function preConsumerPaymentReq($request)
    {
        $mWaterConsumerActiveRequest    = new WaterConsumerActiveRequest();
        $mWaterConsumerCharge           = new WaterConsumerCharge();
        $isRequestActive = $mWaterConsumerActiveRequest->getRequestById($request->applicationId)
            ->where('water_consumer_active_requests.payment_status', 0)
            ->first();
        if (!$isRequestActive) {
            throw new Exception("Active request not found!");
        }
        $isConsumerChargeExist = $mWaterConsumerCharge->getConsumerChargesById($isRequestActive->id)->where('paid_status', 0)->first();
        if (!$isConsumerChargeExist) {
            throw new Exception("Charges for respective Application not found!");
        }
        return [
            "totalAmount"       => $isConsumerChargeExist->amount,
            "ulbWorkflowId"     => $isRequestActive->workflow_id,
            "ulbId"             => $isRequestActive->ulb_id,
            "penaltyAmount"     => $isConsumerChargeExist->penalty ?? null,
            "chargeCatagoryId"  => $isConsumerChargeExist->charge_category_id
        ];
    }


    /**
     * | End the online consumer Request Payment 
        | Serial No :
        | Not Tested
     */
    public function endOnlineConReqPayment($webhookData, $RazorPayRequest)
    {
        try {
            # ref var assigning
            $status         = 1;
            $today          = Carbon::now();
            $refUserId      = $webhookData["userId"];
            $refUlbId       = $webhookData["ulbId"];
            $applicationId  = $webhookData["id"];

            # model assigning
            $mWaterTran                     = new WaterTran();
            $mWaterTranDetail               = new WaterTranDetail();
            $mWaterRazorPayResponse         = new WaterRazorPayResponse();
            $mWaterConsumerCharge           = new WaterConsumerCharge();
            $mWaterConsumerActiveRequest    = new WaterConsumerActiveRequest();

            # variable assigning
            $consumerReqDetails = $mWaterConsumerActiveRequest->getActiveReqById($applicationId)
                ->where('payment_status', 0)
                ->first();
            if (!$consumerReqDetails) {
                throw new Exception("Application detais not found!");
            }
            $demandDetails = $mWaterConsumerCharge->getConsumerChargesById($applicationId)
                ->where('paid_status', 0)
                ->where('charge_category_id', $RazorPayRequest->conumer_charge_id)
                ->first();
            if (!$demandDetails) {
                throw new Exception("Consumer Charges not found!");
            }
            $this->checkPaymentRequest($RazorPayRequest, $webhookData, $demandDetails, $consumerReqDetails);

            DB::beginTransaction();
            # save payment data in razorpay response table
            $paymentResponseId = $mWaterRazorPayResponse->savePaymentResponse($RazorPayRequest, $webhookData);

            # save the razorpay request status as 1
            $RazorPayRequest->status = 1;                                       // Static
            $RazorPayRequest->update();

            # save data in water transaction table 
            $metaRequest = [
                "id"                => $webhookData["id"],
                'amount'            => $webhookData['amount'],
                'chargeCategory'    => $RazorPayRequest->payment_from,
                'todayDate'         => $today,
                'tranNo'            => $webhookData["transactionNo"],
                'paymentMode'       => "Online",                                // Static
                'citizenId'         => $refUserId,
                'userType'          => "Citizen" ?? null,                       // Check here // Static
                'ulbId'             => $refUlbId,
                'leftDemandAmount'  => $RazorPayRequest->due_amount,
                'adjustedAmount'    => $RazorPayRequest->adjusted_amount,
                'pgResponseId'      => $paymentResponseId['razorpayResponseId'],
                'pgId'              => $webhookData['gatewayType']
            ];
            $consumer['ward_mstr_id'] = $consumerReqDetails->ward_mstr_id;
            $transactionId = $mWaterTran->waterTransaction($metaRequest, $consumer);

            # save Water trans details 
            $mWaterTranDetail->saveDefaultTrans($demandDetails->amount, $demandDetails->related_id, $transactionId['id'], $demandDetails->id);

            # Save the payment Status and the initiater in active Table
            $updateStatus = [
                "payment_status"    => $status,
                "current_role"      => $consumerReqDetails->initiator
            ];
            $mWaterConsumerActiveRequest->updateDataForPayment($consumerReqDetails->id, $updateStatus);
            DB::commit();
            $res['transactionId'] = $transactionId['id'];
            return responseMsg(true, "Data saved succesfully!", $res);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), $webhookData);
        }
    }


    /**
     * | Check the payment request Amount and existence
        | Serial No :
        | Not Tested
     */
    public function checkPaymentRequest($RazorPayRequest, $webhookData, $demandDetails, $consumerReqDetails)
    {
        if (!$consumerReqDetails) {
            throw new Exception("Application detial not found!");
        }
        if (!$RazorPayRequest || round($webhookData['amount']) != round($RazorPayRequest['amount'])) {
            throw new Exception("Payble Amount Missmatch!!!");
        }
        if (round($demandDetails->amount) != round($webhookData['amount'])) {
            throw new Exception("Charge amount is not matched!");
        }
    }


    /**
     * | Offline Paymet for Water Consumer request
        | Serial No :
        | Under Con
     */
    public function offlineConReqPayment(reqConsumerReqPayment $request)
    {
        try {
            $user           = authUser($request);
            $todayDate      = Carbon::now();
            $applicatinId   = $request->applicationId;
            $refPaymentMode = Config::get('payment-constants.REF_PAY_MODE');

            $idGeneration                   = new IdGeneration;
            $mWaterTran                     = new WaterTran();
            $mWaterConsumerActiveRequest    = new WaterConsumerActiveRequest();
            $mWaterConsumerCharge           = new WaterConsumerCharge();

            $offlinePaymentModes = Config::get('payment-constants.VERIFICATION_PAYMENT_MODES');
            $activeConRequest = $mWaterConsumerActiveRequest->getActiveReqById($applicatinId)
                ->where('payment_status', 0)
                ->first();
            if (!$activeConRequest) {
                throw new Exception("Application details not found!");
            }
            if ($request->paymentMode == 'ONLINE') {                                // Static
                throw new Exception("Online mode is not accepted!");
            }

            $activeConsumercharges = $mWaterConsumerCharge->getConsumerChargesById($applicatinId)
                ->where('charge_category_id', $activeConRequest->charge_catagory_id)
                ->where('paid_status', 0)
                ->first();
            if (!$activeConsumercharges) {
                throw new Exception("Consumer Charges not found!");
            }
            $chargeCatagory = $this->checkConReqPayment($activeConRequest);

            DB::beginTransaction();
            $tranNo = $idGeneration->generateTransactionNo($user->ulb_id);
            $request->merge([
                'userId'            => $user->id,
                'userType'          => $user->user_type,
                'todayDate'         => $todayDate->format('Y-m-d'),
                'tranNo'            => $tranNo,
                'id'                => $applicatinId,
                'ulbId'             => $user->ulb_id,
                'chargeCategory'    => $chargeCatagory['chargeCatagory'],                                 // Static
                'isJsk'             => true,
                'amount'            => $activeConsumercharges->amount,
                'paymentMode'       => $refPaymentMode[$request->paymentMode]
            ]);

            # Save the Details of the transaction
            $wardId['ward_mstr_id'] = $activeConRequest->ward_mstr_id;
            $waterTrans = $mWaterTran->waterTransaction($request, $wardId);

            # Save the Details for the Cheque,DD,neft
            if (in_array($request['paymentMode'], $offlinePaymentModes)) {
                $request->merge([
                    'chequeDate'    => $request['chequeDate'],
                    'tranId'        => $waterTrans['id'],
                    'applicationNo' => $activeConRequest->application_no,
                    'workflowId'    => $activeConRequest->workflow_id,                                                   // Static
                    'ward_no'       => $activeConRequest->ward_mstr_id
                ]);
                $this->postOtherRequestPay($request);
            }
            # Save the transaction details for offline mode  
            $this->saveConsumerRequestStatus($request, $offlinePaymentModes, $activeConsumercharges, $waterTrans, $activeConRequest);
            DB::commit();
            return responseMsgs(true, "Payment Done!", remove_null($request->all()), "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "03", ".ms", "POST", $request->deviceId);
        }
    }

    /**
     * | Check if the transaction exist
        | Serial No :
        | Under con    
     */
    public function checkConReqPayment($applicationDetails)
    {
        $ref = Config::get('waterConstaint.PAYMENT_FOR');
        $mWaterTran = new WaterTran();
        $transDetails = $mWaterTran->getTransNoForConsumer($applicationDetails->id, $ref["$applicationDetails->charge_catagory_id"])->first();
        if ($transDetails) {
            throw new Exception("Transaction details is present in Database!");
        }
        return [
            "chargeCatagory" => $ref["$applicationDetails->charge_catagory_id"]
        ];
    }

    /**
     * | Post other payment request
        | Serial No :
        | Under Con
     */
    public function postOtherRequestPay($req)
    {
        $cash               = Config::get('payment-constants.PAYMENT_MODE.3');
        $moduleId           = Config::get('module-constants.WATER_MODULE_ID');
        $mTempTransaction   = new TempTransaction();
        $mPropChequeDtl     = new WaterChequeDtl();

        if ($req['paymentMode'] != $cash) {
            if ($req->chargeCategory == "Demand Collection") {
                $chequeReqs = [
                    'user_id'           => $req['userId'],
                    'consumer_req_id'   => $req['id'],
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
                'ward_no'           => $req['ward_no']
            ];
            $mTempTransaction->tempTransaction($tranReqs);
        }
    }

    /**
     * | Save the status in active consumer table, transaction, 
        | Serial No :
        | Under Con
     */
    public function saveConsumerRequestStatus($request, $offlinePaymentModes, $charges, $waterTrans, $activeConRequest)
    {
        $mWaterConsumerActiveRequest    = new WaterConsumerActiveRequest();
        $waterTranDetail                = new WaterTranDetail();
        $mWaterTran                     = new WaterTran();

        if (in_array($request['paymentMode'], $offlinePaymentModes)) {
            $charges->paid_status = 2;                                       // Update Demand Paid Status // Static
            $mWaterTran->saveVerifyStatus($waterTrans['id']);
            $refReq = [
                "payment_status" => 2,
            ];
            $mWaterConsumerActiveRequest->updateDataForPayment($activeConRequest->id, $refReq);
        } else {
            $charges->paid_status = 1;                                      // Update Demand Paid Status // Static
            $refReq = [
                "payment_status"    => 1,
                "current_role"      => $activeConRequest->initiator
            ];
            $mWaterConsumerActiveRequest->updateDataForPayment($activeConRequest->id, $refReq);
        }
        $charges->save();                                                   // Save Demand
        $waterTranDetail->saveDefaultTrans(
            $charges->amount,
            $request->consumerId ?? $request->applicationId,
            $waterTrans['id'],
            $charges['id'],
        );
    }
}
