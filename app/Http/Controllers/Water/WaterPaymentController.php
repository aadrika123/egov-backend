<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Models\Payment\WebhookPaymentData;
use App\Models\Water\WaterApplication;
use App\Models\Water\WaterConsumer;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterSiteInspection;
use App\Models\Water\WaterTran;
use App\Models\Water\WaterTranDetail;
use App\Repository\Water\Concrete\WaterNewConnection;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

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
    // water transaction Details

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

            $waterDtls = $mWaterConsumer->getConsumerDetailById($request->consumerId);
            if (!$waterDtls)
                throw new Exception("Water Consumer Not Found!");

            $waterTrans = $mWaterTran->ConsumerTransaction($request->consumerId)->get();         // Water Consumer Payment History
            $waterTrans = collect($waterTrans)->map(function ($value, $key) use ($mWaterConsumerDemand, $mWaterTranDetail) {
                $demandId = $mWaterTranDetail->getDetailByTranId($value['id']);
                $value['demand'] = $mWaterConsumerDemand->getDemandBydemandId($demandId['demand_id']);
                return $value;
            });

            if (!$waterTrans || $waterTrans->isEmpty())
                throw new Exception("No Transaction Found!");

            $applicationId = $waterDtls->apply_connection_id;
            if (!$applicationId)
                throw new Exception("This Property has not ApplicationId!!");

            $connectionTran[] = $mWaterTran->getTransNo($applicationId, null)->first();                        // Water Connection payment History

            if (!$connectionTran)
                throw new Exception("Water Application Tran Details not Found!!");

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

   
}
