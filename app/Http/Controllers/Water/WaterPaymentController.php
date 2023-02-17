<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Http\Requests\Water\siteAdjustment;
use App\Models\Payment\WebhookPaymentData;
use App\Models\Water\WaterApplication;
use App\Models\Water\WaterConnectionCharge;
use App\Models\Water\WaterConnectionThroughMstr;
use App\Models\Water\WaterConnectionTypeMstr;
use App\Models\Water\WaterConsumer;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterOwnerTypeMstr;
use App\Models\Water\WaterParamPipelineType;
use App\Models\Water\WaterPenaltyInstallment;
use App\Models\Water\WaterPropertyTypeMstr;
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
                'water-connection-type-mstr'    => $waterConnectionTypeMstr,
                'water-connection-through-mstr' => $waterConnectionThroughMstr,
                'water-property-type-mstr'      => $waterPropertyTypeMstr,
                'water-owner-type-mstr'         => $waterOwnerTypeMstr,
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
        | Not Finish
     */
    public function generateDemandPaymentReceipt(Request $req)
    {
        $req->validate([
            'transactionNo' => 'required'
        ]);
        try {
            $refTransactionNo = $req->transactionNo;
            $mWaterConsumer = new WaterConsumer();
            $mWaterTran = new WaterTran();

            $mTowards = Config::get('waterConstaint.TOWARDS');
            $mAccDescription = Config::get('waterConstaint.ACCOUNT_DESCRIPTION');
            $mDepartmentSection = Config::get('waterConstaint.DEPARTMENT_SECTION');

            $responseData = collect($refTransactionNo)->map(function ($value, $key) use (
                $mWaterConsumer,
                $mWaterTran,
                $mTowards,
                $mAccDescription,
                $mDepartmentSection,
            ) {
                # Transaction Details according to transaction no
                $transactionDetails = $mWaterTran->getTransactionByTransactionNo($value);

                # Consumer Deails and demand details
                $consumerDetails = $mWaterConsumer->getConsumerListById($transactionDetails->related_id, $transactionDetails->demand_id);

                # Transaction Date
                $refDate = $transactionDetails->tran_date;
                $transactionDate = Carbon::parse($refDate)->format('Y-m-d');

                # transaction time
                // $epoch = $webhookDetails->payment_created_at;
                // $dateTime = new DateTime("@$epoch");
                // $transactionTime = $dateTime->format('H:i:s');

                return [
                    "departmentSection" => $mDepartmentSection,
                    "accountDescription" => $mAccDescription,
                    "transactionDate" => $transactionDate,
                    "transactionNo" => $value,
                    // "transactionTime" => $transactionTime,
                    "applicationNo" => "",
                    "customerName" => $consumerDetails->consumer_name,
                    "customerMobile" => $consumerDetails->mobile_no,
                    "address" => $consumerDetails->address,
                    "paidFrom" => $consumerDetails->demand_from,
                    "paidFromQtr" => "",
                    "paidUpto" => $consumerDetails->demand_upto,
                    "paidUptoQtr" => $consumerDetails->demand_upto,
                    "paymentMode" => $transactionDetails->payment_mode,
                    "bankName" => "",                                   // in case of cheque,dd,nfts
                    "branchName" => "",                                 // in case of chque,dd,nfts
                    "chequeNo" => "",                                   // in case of chque,dd,nfts
                    "chequeDate" => "",                                 // in case of chque,dd,nfts
                    "monthlyRate" => "",
                    "demandAmount" => $consumerDetails->amount,
                    "taxDetails" => "",
                    "ulbId" => $consumerDetails->ulb_id,
                    "ulbName" => $consumerDetails->ulb_name,
                    "WardNo" => $consumerDetails->old_ward_name,
                    "towards" => $mTowards,
                    "description" => $mAccDescription,
                    "totalPaidAmount" => $transactionDetails->amount,
                    "paidAmtInWords" => getIndianCurrency($transactionDetails->amount),
                ];
            });
            return responseMsgs(true, "Payment Receipt", remove_null($responseData), "", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
        }
    }


    /**
     * | Site Inspection Details Entry
     * | Save the adjusted Data
     * | @param request
     * | @var 
     * | @return
        | Serial No : 04
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
                ->where('charge_category', $connectionCatagory['NEW_CONNECTION'])
                ->firstOrFail();
            $oldChargeAmount = $applicationCharge['amount'];

            # Generating Demand for new InspectionData
            $newConnectionCharges = objToArray($mWaterNewConnection->calWaterConCharge($request));
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

            $mWaterSiteInspection->storeInspectionDetails($request, $waterFeeId);
        } catch (Exception $e) {
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
            'chargeCatagory' => $chargeCatagory['SITE_INSPECTON']
        ]);
        $connectionId = $mWaterConnectionCharge->saveWaterCharge($applicationId, $request, $newConnectionCharges);
        # in case of connection charge is 0
        if ($newCharge == 0) {
            $mWaterTran->saveZeroConnectionCharg($newCharge, $waterApplicationDetails->ulb_id, $request, $applicationId, $connectionId);
        }
    }
}
