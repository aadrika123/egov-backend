<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterRazorPayRequest extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $connection = 'pgsql_water';

    /**
     * | Save data for the payment requests
     * | @param
     */
    public function saveRequestData($request, $paymentFor, $temp, $refDetails)
    {
        $RazorPayRequest = new WaterRazorPayRequest;
        $RazorPayRequest->related_id            = $request->consumerId ?? $request->applicationId;
        $RazorPayRequest->payment_from          = $paymentFor;
        $RazorPayRequest->amount                = $request->amount ?? $refDetails['totalAmount'];
        $RazorPayRequest->demand_from_upto      = $request->demandFrom ? ($request->demandFrom . "--" . $request->demandUpto) : null;
        $RazorPayRequest->ip_address            = $request->ip();
        $RazorPayRequest->order_id              = $temp["orderId"];
        $RazorPayRequest->department_id         = $temp["departmentId"] ?? 2;
        $RazorPayRequest->adjusted_amount       = $refDetails["adjustedAmount"] ?? null;
        $RazorPayRequest->due_amount            = $refDetails["leftDemandAmount"] ?? null; # dont save 
        $RazorPayRequest->penalty_amount        = $refDetails["penaltyAmount"] ?? null;
        $RazorPayRequest->remarks               = $request->remarks;
        $RazorPayRequest->consumer_charge_id    = $refDetails['chargeCatagoryId'] ?? null;
        $RazorPayRequest->save();
    }

    /**
     * | Save data for the payment requests
     * | @param
     */
    public function saveRequestDatav1($consumer, $request, $paymentFor, $refDetails, $consumerData)
    {
        $RazorPayRequest = new WaterRazorPayRequest;
        $RazorPayRequest->related_id            = $consumer['consumerId']; // Get from consumerDetails
        $RazorPayRequest->payment_from          = $paymentFor;
        $RazorPayRequest->amount                = $consumer['amount']; // Use per-consumer amount
        $RazorPayRequest->demand_from_upto      = $consumer['demandFrom'] . "--" . $consumer['demandUpto'];
        // $RazorPayRequest->ip_address            = $request->ip();
        $RazorPayRequest->order_id              = $refDetails["order_id"];
        // $RazorPayRequest->department_id         = $refDetails["departmentId"] ?? 2;
        $RazorPayRequest->adjusted_amount       = $consumerData["adjustedAmount"] ?? null;
        $RazorPayRequest->due_amount            = $consumerData["leftDemandAmount"] ?? null; // Don't save
        $RazorPayRequest->penalty_amount        = $consumerData["penaltyAmount"] ?? null;
        $RazorPayRequest->remarks               = $consumer['remarks']; // Use per-consumer remarks
        $RazorPayRequest->consumer_charge_id    = $consumerData['chargeCatagoryId'] ?? null;
        $RazorPayRequest->save();
    }


    /**
     * | Get 
     */
    public function checkRequest($webhookData)
    {
        return WaterRazorPayRequest::select("*")
            ->where("order_id", $webhookData["orderId"])
            ->where("related_id", $webhookData["id"])
            ->where("status", 2);
    }

    /**
     * | Get Razor pay request by order id and saf id
     * | @param Request $req
     */
    public function getRazorPayRequestsv1($req)
    {
        return WaterRazorPayRequest::where('order_id', $req['orderId'])
            // ->where("$req->key", $req->keyId)
            ->orderByDesc('id')
            ->get();
    }
}
