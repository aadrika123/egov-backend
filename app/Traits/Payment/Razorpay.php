<?php

namespace App\Traits\Payment;

use App\Models\Paymnet\CardDetail;
use App\Models\Paymnet\PaymentReject;
use App\Models\Paymnet\PaymentSuccess;
use App\Models\Payment\PaymentRequest;
use App\Models\Paymnet\WebhookPaymentData;
use Exception;
use Razorpay\Api\Api;
use Illuminate\Support\Str;
use Razorpay\Api\Errors\SignatureVerificationError;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\NewPdfController; //<----------traits
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * Trait for Razorpay
 * Created By-Sam kerktta
 * Created On-15-11-2022 
 * --------------------------------------------------------------------------------------
 */

trait Razorpay
{
    /**
     * | code : Sam Kerketta
     * | ----------------- payment generating order id / Saving in database ------------------------------- |
     * | @var validated 
     * | @var reciptId
     * | @var api
     * | @var order
     * | @var data
     * | @var tran
     * | @var error
     * | @param request
     * | Operation : generating the order id according the request data using the razorpay API 
     */

    public function saveGenerateOrderid($request)
    {
        #code....
        $validated = Validator::make(
            $request->all(),
            [
                'amount' => 'required|max:200',
                'apppartmentId' => 'unique:payment_requests, apppartment_id',
                'consumerId' => 'unique:payment_requests, consumer_id'
            ]
        );
        if ($validated->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validated->errors()
            ], 401);
        }

        DB::beginTransaction(); //<----------- here(CAUTION)
        try {
            $mReciptId = Str::random(10);
            $mApi = new Api($this->razorpayId, $this->razorpayKey);

            $mOrder = $mApi->order->create(array(
                'receipt' => $mReciptId,
                'amount' => $request->all()['amount'] * 100,
                'currency' => 'INR',
                'payment_capture' => 1
            ));
            $mReturndata = [
                'orderId' => $mOrder['id'],
                'amount' => $request->all()['amount'],
                'currency' => 'INR',

            ];
            #   (condition applied) 
            //storing the detail in the database
            $transaction = new PaymentRequest();
            $transaction->apppartment_id = $request->apppartmentId; //<--------- here
            $transaction->consumer_id = $request->consumerId; //<--------- here
            $transaction->razorpay_order_id = $mReturndata['orderId'];
            $transaction->amount = $request->amount;
            $transaction->currency = 'INR';
            $transaction->save();

            // DB::commit(); //<----------- here(CAUTION)
            return responseMsg(true, "Order Id Generated!", $mReturndata);
        } catch (Exception $error) {
            return responseMsg(false, "Some Error Listed Below!", $error);
        }
    }
}
