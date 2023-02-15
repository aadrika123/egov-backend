<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment\PaymentReconciliation;
use App\Models\Property\PropTransaction;
use App\Models\Trade\TradeTransaction;
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
    public function transactionDtl()
    {
    }

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
                $data =  PropTransaction::select('prop_transactions.*', 'prop_cheque_dtls.*')
                    ->leftjoin('prop_cheque_dtls', 'prop_cheque_dtls.transaction_id', 'prop_transactions.id')
                    ->where('payment_mode', 'DD')
                    ->orWhere('payment_mode', 'CHEQUE')
                    ->whereBetween('tran_date', [$fromDate, $toDate])
                    ->where('ulb_id', $ulbId)
                    ->get();
            }

            if ($moduleId == $tradeModuleId) {
                $data = TradeTransaction::select('*')
                    ->where('payment_mode', 'DD')
                    ->orWhere('payment_mode', 'CHEQUE')
                    ->whereBetween('tran_date', [$fromDate, $toDate])
                    ->where('ulb_id', $ulbId)
                    ->get();
            }

            if ($moduleId == $waterModuleId) {
                $data = WaterTran::select('*')
                    ->where('payment_mode', 'DD')
                    ->orWhere('payment_mode', 'CHEQUE')
                    ->whereBetween('tran_date', [$fromDate, $toDate])
                    ->where('ulb_id', $ulbId)
                    ->get();
            }

            if ($request->chequeNo) {
                collect($data)->where('cheque_no', $request->chequeNo);
            }

            if (!empty(collect($data))) {
                return responseMsg(true, "Data Acording to request!", $data);
            }
            return responseMsg(false, "data not found!", "");
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }
}
