<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RazorpayRequest extends Model
{
    use HasFactory;
    protected $guarded = [];


    /**
     * | Save data for the payment requests
     * | @param
     */
    public function saveRequestData($razorPayRequest, $consumerdtls, $paymentFor, $request ,$refDetails)
    {
        $RazorPayRequest = new RazorpayRequest;
        $RazorPayRequest->order_id             = $razorPayRequest["order_id"];
        $RazorPayRequest->payment_id           = null; // To be updated after payment confirmation
        $RazorPayRequest->saf_id               = $request->safId ?? null;
        $RazorPayRequest->prop_id              = json_encode($request->propId); // Store propId as JSON
        $RazorPayRequest->saf_cluster_id       = $request->safClusterId ?? null;
        $RazorPayRequest->prop_cluster_id      = $request->propClusterId ?? null;
        $RazorPayRequest->from_fyear           = $request->fromFYear ?? null;
        $RazorPayRequest->from_qtr             = $request->fromQtr ?? null;
        $RazorPayRequest->to_fyear             = $request->toFYear ?? null;
        $RazorPayRequest->to_qtr               = $request->toQtr ?? null;
        $RazorPayRequest->demand_amt           = $razorPayRequest['demand_amt'] ?? null;
        $RazorPayRequest->demand_list          = $razorPayRequest['demand_list'] ?? null;
        $RazorPayRequest->related_id           = json_encode(array_column($request->consumerDetails, 'consumerId')); // Store consumerId as JSON
        $RazorPayRequest->payment_from         = $paymentFor;
        
        // Handling demand_from_upto as concatenated range for the first consumer
        $RazorPayRequest->demand_from_upto     = isset($request->consumerDetails[0]['demandFrom']) ? 
                                                ($request->consumerDetails[0]['demandFrom'] . "--" . $request->consumerDetails[0]['demandUpto']) : null;
    
        $RazorPayRequest->merchant_id          = $razorPayRequest["merchantId"] ?? null;
        $RazorPayRequest->signature            = null; // To be updated after payment verification
        $RazorPayRequest->error_reason         = null; // To store any error reasons if payment fails
        $RazorPayRequest->department_id        = $razorPayRequest["departmentId"] ?? null;
        $RazorPayRequest->penalty_id           = $request->penaltyId ?? null;
        $RazorPayRequest->adjusted_amount      = $razorPayRequest["adjustedAmount"] ?? null;
        $RazorPayRequest->penalty_amount       = $razorPayRequest["penaltyAmount"] ?? null;
        $RazorPayRequest->due_amount           = $razorPayRequest["leftDemandAmount"] ?? null;
        $RazorPayRequest->remarks              = $request->consumerDetails[0]['remarks'] ?? null;
        $RazorPayRequest->consumer_charge_id   = $razorPayRequest['chargeCatagoryId'] ?? null;
        $RazorPayRequest->amount               =  $refDetails['amount'] ?? $request->consumerDetails[0]['amount'] ?? $razorPayRequest['totalAmount'];
        $RazorPayRequest->advance_amount       = $request->advanceAmount ?? null;
        $RazorPayRequest->ulb_id               = $request->ulbId;
        $RazorPayRequest->status               = 2; // Default status as per the table
        $RazorPayRequest->ip_address           = $request->ip();
    
        $RazorPayRequest->save();
    }
    
    
    
    
}
