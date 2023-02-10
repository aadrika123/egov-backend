<?php

namespace App\Models\Payment;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempTransaction extends Model
{
    use HasFactory;

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
        $mTempTransaction->ulb_id = $req->ulbId;
        $mTempTransaction->ward_no = $req->wardNo;
        $mTempTransaction->created_at = Carbon::now();
        $mTempTransaction->save();
    }

    public function transactionList($date, $userId, $ulbId)
    {
        return TempTransaction::select(
            'temp_transactions.id',
            'transaction_no as tran_no',
            'payment_mode',
            'cheque_dd_no',
            'bank_name',
            'amount',
            'module_id',
            'ward_no as ward_name',
            'application_no',
            'tran_date',
            'user_name'
        )
            ->join('users', 'users.id', 'temp_transactions.user_id')
            ->where('payment_mode', '!=', 'ONLINE')
            ->where('tran_date', $date)
            ->where('user_id', $userId)
            ->where('temp_transactions.ulb_id', $ulbId)
            ->get();
    }
}
