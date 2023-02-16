<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment\PaymentReconciliation;
use App\Models\Property\PropChequeDtl;
use App\Models\Property\PropTransaction;
use App\Models\Trade\TradeChequeDtl;
use App\Models\Trade\TradeTransaction;
use App\Models\Water\WaterChequeDtl;
use App\Models\Water\WaterTran;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;

/**
 * | Created On-14-02-2023 
 * | Created by-Mrinal Kumar
 * | Bank Reconcillation
 */

class BankReconcillationController extends Controller
{
    /**
     * |
     */
    public function searchTransaction(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fromDate' => 'required',
                'toDate' => 'required',
                'moduleId' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => False, 'msg' => $validator()->errors()]);
            }
            $ulbId = authUser()->ulb_id;
            $moduleId = $request->moduleId;
            $paymentMode = $request->paymentMode;
            $fromDate = Carbon::create($request->fromDate)->format('Y-m-d');
            $toDate = Carbon::create($request->toDate)->format('Y-m-d');
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');
            $mPropTransaction = new PropTransaction();
            $mTradeTransaction = new TradeTransaction();
            $mWaterTran = new WaterTran();

            if ($moduleId == $propertyModuleId) {
                $chequeTranDtl  = $mPropTransaction->chequeTranDtl($ulbId);

                if ($request->chequeNo) {
                    $data =  $chequeTranDtl
                        ->where('cheque_no', $request->chequeNo)
                        ->first();
                }
                if (!isset($data)) {
                    return  $data = $chequeTranDtl
                        ->whereBetween('tran_date', [$fromDate, $toDate])
                        ->get();
                }
            }

            if ($moduleId == $tradeModuleId) {
                $chequeTranDtl  = $mTradeTransaction->chequeTranDtl($ulbId);

                if ($request->chequeNo) {
                    $data =  $chequeTranDtl
                        ->where('cheque_no', $request->chequeNo)
                        ->first();
                }
                if (!isset($data)) {
                    return  $data = $chequeTranDtl
                        ->whereBetween('tran_date', [$fromDate, $toDate])
                        ->get();
                }
            }

            if ($moduleId == $waterModuleId) {

                $chequeTranDtl  = $mWaterTran->chequeTranDtl($ulbId);

                if ($request->chequeNo) {
                    $data =  $chequeTranDtl
                        ->where('cheque_no', $request->chequeNo)
                        ->first();
                }
                if (!isset($data)) {
                    return  $data = $chequeTranDtl
                        ->whereBetween('tran_date', [$fromDate, $toDate])
                        ->get();
                }
            }
            //

            if ($paymentMode == 'DD') {
                $a =  collect($data)->where('payment_mode', 'DD');
                $data = (array_values(objtoarray($a)));
            }

            if ($paymentMode == 'CHEQUE') {
                $a =  collect($data)->where('payment_mode', 'CHEQUE');
                $data = (array_values(objtoarray($a)));
            }

            //search with verification status is pending


            if (!empty(collect($data))) {
                return responseMsg(true, "Data Acording to request!", $data);
            }
            return responseMsg(false, "data not found!", "");
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }


    /**
     * |
     */
    public function chequeDtlById(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'moduleId' => 'required',
                'chequeId' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => False, 'msg' => $validator()->errors()]);
            }

            $moduleId = $request->moduleId;
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');
            $mPropChequeDtl = new PropChequeDtl();
            $mTradeChequeDtl = new TradeChequeDtl();
            $mWaterChequeDtl = new WaterChequeDtl();


            switch ($moduleId) {
                    //Property
                case ($propertyModuleId):
                    $data = $mPropChequeDtl->chequeDtlById($request);
                    break;

                    //Water
                case ($waterModuleId):
                    $data = $mWaterChequeDtl->chequeDtlById($request);
                    break;

                    //Trade
                case ($tradeModuleId):
                    $data = $mTradeChequeDtl->chequeDtlById($request);
                    break;
            }

            if ($data)
                return responseMsg(true, "data found", $data);
            else
                return responseMsg(false, "data not found!", "");
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }

    /**
     * |
     */
    public function chequeClearance(Request $request)
    {
        try {
            // $validator = Validator::make($request->all(), [
            //     'moduleId' => 'required',
            //     'chequeId' => 'required',
            //     'status' => 'required'
            //     'remarks' => 'required'
            //     'clearanceDate'=> 'required'|date
            // ]);

            // if ($validator->fails()) {
            //     return response()->json(['status' => False, 'msg' => $validator()->errors()]);
            // }

            $ulbId = authUser()->ulb_id;
            $userId = authUser()->user_id;
            $moduleId = $request->moduleId;
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');
            $mPaymentReconciliation = new PaymentReconciliation();


            if ($moduleId == $propertyModuleId) {
                $mChequeDtl =  PropChequeDtl::find($request->chequeId);
                $mChequeDtl->status = $request->status;
                $mChequeDtl->clear_bounce_date = $request->clearanceDate;
                $mChequeDtl->bounce_amount = $request->cancellationCharge;
                $mChequeDtl->remarks = $request->remarks;
                $mChequeDtl->save();

                $transaction = PropTransaction::where('id', $mChequeDtl->transaction_id)
                    ->first();

                $request->merge([
                    'id' => $mChequeDtl->id,
                    'paymentMode' => $transaction->payment_mode,
                    'transactionNo' => $transaction->transaction_no,
                    'transactionAmount' => $transaction->transaction_amount,
                    'transactionDate' => $transaction->transaction_date,
                    'wardNo' => $request->wardNo,
                    'chequeNo' => $mChequeDtl->cheque_no,
                    'branchName' => $mChequeDtl->branch_name,
                    'bankName' => $mChequeDtl->bank_name,
                    'clearanceDate' => $mChequeDtl->clearanceDate,
                    'bounceReason' => $mChequeDtl->bounce_reason,
                    'chequeDate' => $mChequeDtl->cheque_date,
                    'moduleId' => $propertyModuleId,
                    'ulbId' => $ulbId,
                    'userId' => $userId,
                ]);

                // return $request;
                $mPaymentReconciliation->addReconcilation($request);
            }

            if ($moduleId == $waterModuleId) {
                $mChequeDtl =  WaterChequeDtl::find($request->chequeId);
                $mChequeDtl->status = $request->status;
                $mChequeDtl->clear_bounce_date = $request->clearanceDate;
                $mChequeDtl->bounce_amount = $request->cancellationCharge;
                $mChequeDtl->remarks = $request->remarks;
                $mChequeDtl->save();

                $transaction = WaterTran::where('id', $mChequeDtl->transaction_id)
                    ->first();

                $request->merge([
                    'id' => $mChequeDtl->id,
                    'paymentMode' => $transaction->payment_mode,
                    'transactionNo' => $transaction->transaction_no,
                    'transactionAmount' => $transaction->transaction_amount,
                    'transactionDate' => $transaction->transaction_date,
                    'wardNo' => $request->wardNo,
                    'chequeNo' => $mChequeDtl->cheque_no,
                    'branchName' => $mChequeDtl->branch_name,
                    'bankName' => $mChequeDtl->bank_name,
                    'clearanceDate' => $mChequeDtl->clearanceDate,
                    'bounceReason' => $mChequeDtl->bounce_reason,
                    'chequeDate' => $mChequeDtl->cheque_date,
                    'moduleId' => $waterModuleId,
                    'ulbId' => $ulbId,
                    'userId' => $userId,
                ]);

                // return $request;
                $mPaymentReconciliation->addReconcilation($request);
            }

            if ($moduleId == $tradeModuleId) {
                $mChequeDtl =  TradeChequeDtl::find($request->chequeId);
                $mChequeDtl->state = $request->status;
                $mChequeDtl->clear_bounce_date = $request->clearanceDate;
                $mChequeDtl->bounce_amount = $request->cancellationCharge;
                $mChequeDtl->remarks = $request->remarks;
                $mChequeDtl->save();

                $transaction = TradeTransaction::where('id', $mChequeDtl->tran_id)
                    ->first();

                $request->merge([
                    'id' => $mChequeDtl->id,
                    'paymentMode' => $transaction->payment_mode,
                    'transactionNo' => $transaction->tran_no,
                    'transactionAmount' => $transaction->paid_amount,
                    'transactionDate' => $transaction->tran_date,
                    'wardNo' => $request->wardNo,
                    'chequeNo' => $mChequeDtl->cheque_no,
                    'branchName' => $mChequeDtl->branch_name,
                    'bankName' => $mChequeDtl->bank_name,
                    'clearanceDate' => $mChequeDtl->clearanceDate,
                    'chequeDate' => $mChequeDtl->cheque_date,
                    'moduleId' => $tradeModuleId,
                    'ulbId' => $ulbId,
                    'userId' => $userId,
                ]);

                // return $request;
                $mPaymentReconciliation->addReconcilation($request);
            }
            return responseMsg(true, "Data Updated!", '');
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }
}
