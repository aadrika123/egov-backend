<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempTransaction extends Model
{
    use HasFactory;

    /**
     * | for storing temporary transaction data in temporary transaction table
     */
    public function tempTransaction($req)
    {
        $mTempTransaction = new TempTransaction();
        $mTempTransaction->transaction_id = $req->transactionId;
        $mTempTransaction->application_id = $req->applicationId;
        $mTempTransaction->module_id = $req->moduleId;
        $mTempTransaction->workflow_id = $req->workflowId;
        $mTempTransaction->transaction_no = $req->transactionNo;
        $mTempTransaction->application_no = $req->applicationNo;
        $mTempTransaction->amount = $req->amount;
        $mTempTransaction->payment_mode = $req->paymentNo;
        $mTempTransaction->cheque_dd_no = $req->chequeddNo;
        $mTempTransaction->bank_name = $req->bankName;
        $mTempTransaction->tran_date = $req->tranDate;
        $mTempTransaction->user_id = $req->userId;
        $mTempTransaction->created_at = Carbon::now();
        $mTempTransaction->save();
    }
}
