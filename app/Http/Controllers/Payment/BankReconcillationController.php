<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment\PaymentReconciliation;
use App\Models\Property\PropTransaction;
use App\Models\Trade\TradeTransaction;
use App\Models\Water\WaterTran;
use Exception;
use Illuminate\Http\Request;

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
    public function searchTransaction()
    {
    }

    /**
     * |
     */
    public function transactionDtl(Request $request)
    {
        try {
            $mPropTransaction = new PropTransaction();
            $mTradeTransaction = new TradeTransaction();
            $mWaterTran = new WaterTran();

            return $a =  PropTransaction::where('payment_mode', '!=', 'ONLINE')
                ->get();





            $reconciliationDetails = PaymentReconciliation::select(
                'ulb_id AS ulbId',
                'department_id AS dpartmentId',
                'transaction_no AS transactionNo',
                'payment_mode AS paymentMode',
                'date AS transactionDate',
                'status',
                'cheque_no AS chequeNo',
                'cheque_date AS chequeDate',
                'branch_name AS branchName',
                'bank_name AS bankName',
                'transaction_amount AS amount',
                'clearance_date AS clearanceDate'
            )
                ->where('cheque_no', $request->chequeDdNo)
                ->get();

            if (!empty(collect($reconciliationDetails)->first())) {
                return responseMsg(true, "Data Acording to request!", $reconciliationDetails);
            }
            return responseMsg(false, "data not found!", "");
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }
}
