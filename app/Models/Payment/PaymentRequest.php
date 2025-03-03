<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRequest extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_master';


    /**
     * |--------------------- Save the details of the Payment requests -------------------------------------|
     * | @param request
     * | @param userId
     * | @param UlbId
     * | @param orderId
     * | @var transaction 
        | (Working)
     */
    public function saveRazorpayRequest($userId, $ulbId, $orderId, $request)
    {
        $transaction = new PaymentRequest();
        $transaction->user_id = $userId;
        $transaction->workflow_id = $request->workflowId;
        $transaction->ulb_id = $ulbId;
        $transaction->application_id = $request->id;
        $transaction->department_id = $request->departmentId;                       //<--------here(CHECK)
        $transaction->razorpay_order_id = $orderId;
        $transaction->amount = $request->amount;
        $transaction->currency = 'INR';
        $transaction->save();
    }
    // multiple module payment request
    public function saveRazorpayRequestv1($userId, $ulbId, $orderId, $request)
    {
        $transaction = new PaymentRequest();
        $transaction->user_id = $userId;
        $transaction->workflow_id = $request->workflowId;
        $transaction->ulb_id = $ulbId;
        // Convert propId and consumerId to comma-separated strings
        $propIds = isset($request['propId']) ? implode(',', $request['propId']) : null;
        $consumerIds = isset($request['consumerDetails']) ? implode(',', array_column($request['consumerDetails'], 'consumerId')) : null;

        // Store both propIds and consumerIds in application_id (adjust as per DB structure)
        $transaction->application_id = $propIds . '|' . $consumerIds;  // Example format: "264524,22|78058,7474"
        
        $transaction->department_id = $request->departmentId;                       //<--------here(CHECK)
        $transaction->razorpay_order_id = $orderId;
        $transaction->amount = $request->amount;
        $transaction->currency = 'INR';
        $transaction->save();
    }
}
