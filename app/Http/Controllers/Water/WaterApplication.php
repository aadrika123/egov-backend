<?php

namespace App\Http\Controllers\water;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Water\NewConnectionController;
use App\MicroServices\IdGeneration;
use App\Models\Payment\TempTransaction;
use App\Models\Water\WaterApplicant;
use App\Models\Water\WaterApplication as WaterWaterApplication;
use App\Models\Water\WaterChequeDtl;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterPenaltyInstallment;
use App\Models\Water\WaterTran;
use App\Models\Water\WaterTranDetail;
use App\Repository\Water\Interfaces\iNewConnection;
use App\Repository\Water\Interfaces\IWaterNewConnection;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class WaterApplication extends Controller
{
    private $Repository;
    private $_NewConnectionController;
    public function __construct(IWaterNewConnection $Repository, iNewConnection $newConnection)
    {
        $this->Repository = $Repository;
        $this->_NewConnectionController = new NewConnectionController($newConnection);
    }

    public function applyApplication(Request $request)
    {
        return $this->Repository->applyApplication($request);
    }
    public function getCitizenApplication(Request $request)
    {
        try {
            $returnValue = $this->Repository->getCitizenApplication($request);
            return responseMsg(true, "", remove_null($returnValue));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
    public function handeRazorPay(Request $request)
    {
        return $this->Repository->handeRazorPay($request);
    }
    public function readTransectionAndApl(Request $request)
    {
        return $this->Repository->readTransectionAndApl($request);
    }
    public function paymentRecipt(Request $request)
    {
        $request->validate([
            'transectionNo' => 'required'
        ]);
        return $this->Repository->paymentRecipt($request->transectionNo);
    }
    public function documentUpload(Request $request)
    {
        return $this->Repository->documentUpload($request);
    }
    public function getUploadDocuments(Request $request)
    {
        return $this->Repository->getUploadDocuments($request);
    }
    public function calWaterConCharge(Request $request)
    {
        return $this->Repository->calWaterConCharge($request);
    }

    public function paymentWater(Request $req)
    {
        try {
            // Variable Assignments
            $offlinePaymentModes = Config::get('payment-constants.PAYMENT_MODE_OFFLINE');
            $todayDate = Carbon::now();
            $waterConsumerDemand = new WaterConsumerDemand();
            $idGeneration = new IdGeneration;
            $waterTran = new WaterTran();
            $userId = $req['userId'];
            if (!$userId)
                $userId = auth()->user()->id ?? 0;                                      // Authenticated user or Ghost User

            $tranNo = $req['transactionNo'];
            // Derivative Assignments
            if (!$tranNo)
                $tranNo = $idGeneration->generateTransactionNo();
            $demands = $waterConsumerDemand->getConsumerDemand($req['id']);

            if (!$demands || collect($demands)->isEmpty())
                throw new Exception("Demand Not Available for Payment");
            // Property Transactions
            $req->merge([
                'userId' => $userId,
                'todayDate' => $todayDate->format('Y-m-d'),
                'tranNo' => $tranNo
            ]);
            DB::beginTransaction();
            $waterTrans = $waterTran->waterTransaction($req, $demands);

            if (in_array($req['paymentMode'], $offlinePaymentModes)) {
                $req->merge([
                    'chequeDate' => $req['chequeDate'],
                    'tranId' => $waterTrans['id']
                ]);
                $this->postOtherPaymentModes($req);
            }

            // Reflect on Prop Tran Details
            foreach ($demands as $demand) {
                $demand->paid_status = 1;           // <-------- Update Demand Paid Status 
                $demand->save();

                $waterTranDetail = new WaterTranDetail();
                $waterTranDetail->tran_id = $waterTrans['id'];
                $waterTranDetail->saf_demand_id = $demand['id'];
                $waterTranDetail->total_demand = $demand['amount'];
                $waterTranDetail->save();
            }

            // Update SAF Payment Status
            $activeSaf = WaterApplicant::find($req['id']);
            $activeSaf->payment_status = 1;
            $activeSaf->save();

            // Replication Prop Rebates Penalties
            $mWaterPenaltyInstallment = new WaterPenaltyInstallment();
            $rebatePenalties = $mWaterPenaltyInstallment->getPenalRebatesBySafId($req['id']);

            collect($rebatePenalties)->map(function ($rebatePenalty) use ($waterTrans) {
                $replicate = $rebatePenalty->replicate();
                $replicate->setTable('prop_penaltyrebates');
                $replicate->tran_id = $waterTrans['id'];
                $replicate->tran_date = Carbon::now()->format('Y-m-d');
                $replicate->save();
            });

            DB::commit();
            return responseMsgs(true, "Payment Successfully Done",  ['TransactionNo' => $tranNo], "010115", "1.0", "567ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Post Other Payment Modes for Cheque,DD,Neft
     */
    public function postOtherPaymentModes($req)
    {
        $cash = Config::get('payment-constants.PAYMENT_MODE.3');
        $moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');
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
            'application_no' => $req->applicationNo,
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
}
