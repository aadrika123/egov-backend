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
            $fromDate = Carbon::create($request->fromDate)->format('Y-m-d');
            $toDate = Carbon::create($request->toDate)->format('Y-m-d');
            $moduleId = $request->moduleId;
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');
            $ulbId = authUser()->ulb_id;

            $mPropTransaction = new PropTransaction();
            $mTradeTransaction = new TradeTransaction();
            $mWaterTran = new WaterTran();

            if ($moduleId == $propertyModuleId) {
                $data =  PropTransaction::select('prop_transactions.*')
                    ->leftjoin('prop_cheque_dtls', 'prop_cheque_dtls.transaction_id', 'prop_transactions.id')
                    ->where('payment_mode', 'DD')
                    ->orWhere('payment_mode', 'CHEQUE')
                    ->whereBetween('tran_date', [$fromDate, $toDate])
                    ->where('ulb_id', $ulbId)
                    ->get();

                if ($request->chequeNo) {
                    $data =  PropChequeDtl::select('prop_cheque_dtls.*')
                        ->where('cheque_no', $request->chequeNo)
                        ->first();
                }
            }

            if ($moduleId == $tradeModuleId) {
                $data = TradeTransaction::select('*')
                    ->where('payment_mode', 'DD')
                    ->orWhere('payment_mode', 'CHEQUE')
                    ->whereBetween('tran_date', [$fromDate, $toDate])
                    ->where('ulb_id', $ulbId)
                    ->get();

                if ($request->chequeNo) {
                    $data =  TradeChequeDtl::select('trade_cheque_dtls.*')
                        ->where('cheque_no', $request->chequeNo)
                        ->first();
                }
            }

            if ($moduleId == $waterModuleId) {
                $data = WaterTran::select('*')
                    ->where('payment_mode', 'DD')
                    ->orWhere('payment_mode', 'CHEQUE')
                    ->whereBetween('tran_date', [$fromDate, $toDate])
                    ->where('ulb_id', $ulbId)
                    ->get();

                if ($request->chequeNo) {
                    $data =  WaterChequeDtl::select('water_cheque_dtls.*')
                        ->where('cheque_no', $request->chequeNo)
                        ->first();
                }
            }

            $a =  collect($data)->where('payment_mode', 'DD');
            $data = (array_values(objtoarray($a)));



            // if ($request->chequeNo) {
            // collect($data)->where('cheque_no', $request->chequeNo);
            // }

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
            $ulbId = authUser()->ulb_id;
            $userId = authUser()->user_id;

            if ($moduleId == $propertyModuleId) {
                $data =  PropChequeDtl::select('*')
                    ->where('id', $request->chequeId)
                    ->first();
            }

            if ($moduleId == $waterModuleId) {
                $data =  WaterChequeDtl::select('*')
                    ->where('id', $request->chequeId)
                    ->first();
            }

            if ($moduleId == $tradeModuleId) {
                $data =  TradeChequeDtl::select('*')
                    ->where('id', $request->chequeId)
                    ->first();
            }
            if ($data)
                return responseMsg(true, "data found!", $data);
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
            $validator = Validator::make($request->all(), [
                'moduleId' => 'required',
                'chequeId' => 'required',
                'status' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => False, 'msg' => $validator()->errors()]);
            }

            $ulbId = authUser()->ulb_id;
            $userId = authUser()->user_id;
            $moduleId = $request->moduleId;
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');
            $mPaymentReconciliation = new PaymentReconciliation();


            if ($moduleId == $propertyModuleId) {
                $mchequeDtl =  PropChequeDtl::find($request->chequeId);
                $mchequeDtl->status = $request->status;
                $mchequeDtl->clear_bounce_date = $request->clear_bounce_date;
                $mchequeDtl->bounce_amount = $request->bounce_amount;
                $mchequeDtl->remarks = $request->remarks;
                $mchequeDtl->save();
            }

            if ($moduleId == $waterModuleId) {
                $mChequeDtl =  WaterChequeDtl::find($request->chequeId);
                $mChequeDtl->status = $request->status;
                $mChequeDtl->clear_bounce_date = $request->clear_bounce_date;
                $mChequeDtl->bounce_amount = $request->bounce_amount;
                $mChequeDtl->remarks = $request->remarks;
                $mChequeDtl->save();
            }

            if ($moduleId == $tradeModuleId) {
                $mChequeDtl =  TradeChequeDtl::find($request->chequeId);
                $mChequeDtl->status = $request->status;
                $mChequeDtl->clear_bounce_date = $request->clear_bounce_date;
                $mChequeDtl->bounce_amount = $request->bounce_amount;
                $mChequeDtl->remarks = $request->remarks;
                $mChequeDtl->save();
            }

            $mPaymentReconciliation->cheque_dtl_id = $mChequeDtl->id;
            $mPaymentReconciliation->payment_mode = $mChequeDtl->id;
            $mPaymentReconciliation->transaction_no = $mChequeDtl->id;
            $mPaymentReconciliation->bounce_reason = $mChequeDtl->id;
            $mPaymentReconciliation->status = $mChequeDtl->id;
            $mPaymentReconciliation->date = $mChequeDtl->id;
            $mPaymentReconciliation->department_id = $mChequeDtl->id;
            $mPaymentReconciliation->ulb_id = $mChequeDtl->id;
            $mPaymentReconciliation->ward_no = $mChequeDtl->id;
            $mPaymentReconciliation->transaction_date = $mChequeDtl->id;
            $mPaymentReconciliation->cheque_no = $mChequeDtl->id;
            $mPaymentReconciliation->bank_no = $mChequeDtl->id;
            $mPaymentReconciliation->branch_name = $mChequeDtl->id;
            $mPaymentReconciliation->transaction_amount = $mChequeDtl->id;
            $mPaymentReconciliation->clearance_date = $mChequeDtl->id;
            $mPaymentReconciliation->bank_name = $mChequeDtl->id;
            $mPaymentReconciliation->cheque_date = $mChequeDtl->id;
            $mPaymentReconciliation->cancellation_charges = $mChequeDtl->id;
            $mPaymentReconciliation->module_id = $mChequeDtl->id;
            $mPaymentReconciliation->user_id = $mChequeDtl->id;



            $mPaymentReconciliation->save();

            return responseMsg(true, "Data Updated!", '');
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }
}
