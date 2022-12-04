<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentReconciliation extends Model
{
    use HasFactory;

    /**
     * | --------------------------- All Details of Payment Reconciliation ------------------------------- |
     * | Opreration : fetching all the details of the payment Reconcillation for the table 
     * | Rating : 1
     */

    public function allReconciliationDetails()
    {
        return PaymentReconciliation::select(
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
            ->get();
    }
}
