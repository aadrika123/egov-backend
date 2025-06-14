<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropRazorpayRequest extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Store New Razorpay Request
       | Common Function
    */ 
    public function store($req)
    {
        $stored = PropRazorpayRequest::create($req);
        return [
            'razorPayReqId' => $stored->id
        ];
    }

    /**
     * | Get Razor pay request by order id and saf id
       | Common Function
     */
    public function getRazorPayRequests($req)
    {
        return PropRazorpayRequest::where('order_id', $req->orderId)
            ->where("$req->key", $req->keyId)
            ->orderByDesc('id')
            ->first();
    }
    /**
     * | Get Razor pay request by order id and saf id
       | Reference Function : collectWebhookDetailsv1
     */
    public function getRazorPayRequestsv1($req)
    {
        return PropRazorpayRequest::where('order_id', $req['orderId'])
            // ->where("$req->key", $req->keyId)
            ->orderByDesc('id')
            ->get();
    }
}
