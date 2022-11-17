<?php

namespace App\Traits\Payment;

use App\Models\Paymnet\CardDetail;
use App\Models\Payment\PaymentRequest;
use App\Models\Paymnet\WebhookPaymentData;
use Exception;
use Razorpay\Api\Api;
use Illuminate\Support\Str;
use Razorpay\Api\Errors\SignatureVerificationError;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\NewPdfController; //<----------traits
use App\Models\Payment\PaymentReject;
use App\Models\Payment\PaymentSuccess;
use App\Repository\WorkflowMaster\Concrete\WorkflowMap;
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
    private $refRazorpayId = "rzp_test_3MPOKRI8WOd54p";
    private $refRazorpayKey = "k23OSfMevkBszuPY5ZtZwutU";

    /**
     * | code : Sam Kerketta
     * | ----------------- payment generating order id / Saving in database ------------------------------- |
     * | @var validated 
     * | @var mReciptId
     * | @var mApi
     * | @var mOrder
     * | @var mReturndata
     * | @var transaction
     * | @var error
     * | @param request
     * | Operation : generating the order id according the request data using the razorpay API 
     */

    public function saveGenerateOrderid($request)
    {
        try {
            $mUserID = auth()->user()->id;
            $mUlbID = auth()->user()->ulb_id;

            $mReciptId = Str::random(10); //<--------- here (STATIC)
            $mApi = new Api($this->refRazorpayId, $this->refRazorpayKey); //<--------- here (CAUTION)

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
                'userId' => $mUserID,
                'ulbId' => $mUlbID,
                'workflowId' => $request->workflowId, //<-----here(FETCH)
                'applicationId' => $request->id,
                'departmentId' => $request->module

            ];
            #   (condition applied) 
            #   storing the detail in the database
            $transaction = new PaymentRequest();
            $transaction->user_id = $mUserID;
            $transaction->workflow_id = $request->workflowId; //<--------here(FETCH)
            $transaction->ulb_id = $mUlbID;
            $transaction->application_id = $request->id;
            $transaction->department_id = $request->module;
            $transaction->razorpay_order_id = $mReturndata['orderId'];
            $transaction->amount = $request->amount;
            $transaction->currency = 'INR';
            // $transaction->save(); //<--------- here (REMINDER)

            return $mReturndata; //<------------------ here(RESPONSE)
        } catch (Exception $error) {
            return responseMsg(false, "Error Listed Below!", $error->getMessage());
        }
    }

    /**
     * | ----------------- verification of the signature ------------------------------- |
     * | @var validated 
     * | @var reciptId
     * | @var api
     * | @var success
     * | @var data
     * | @var error
     * | @var e
     * | @param request
     * | @param attributes
     * | Operation : generating the order id according the request data using the razorpay API 
     * | this -> naming
     * | here -> variable
     */
    function paymentVerify($request, $attributes)
    {
        try {
            $success = false;
            $error = "Payment Failed";
            # verify the existence of the razerpay Id
            if (!null==($request->razorpayPaymentId) && !empty($request->razorpaySignature)) {
                $api = new Api($this->refRazorpayId, $this->refRazorpayKey);
                try {
                    $attributes = [
                        'razorpay_order_id' => $request->razorpayOrderId,
                        'razorpay_payment_id' => $request->razorpayPaymentId,
                        'razorpay_signature' => $request->razorpaySignature
                    ];
                    $api->utility->verifyPaymentSignature($attributes);
                    $success = true;
                } catch (SignatureVerificationError $exception) {
                    $success = false;
                    $error = $exception->getMessage();
                }
            }
            # validation complete and the storing of data
            if ($success === true) {
                # Update database with success data
                try {
                    $data = new PaymentSuccess();
                    $data->razerpay_order_id = $request->razorpayOrderId;
                    $data->razerpay_payment_id = $request->razorpayPaymentId;
                    $data->razerpay_signature = $request->razorpaySignature;
                    // $data->save();  //<----------- here (RECHECK)

                    return responseMsg(true, "Payment Success!", "");
                } catch (Exception $exception) {
                    return responseMsg(false, "Error listed below", $exception->getMessage());
                }
            }
            # Update database with error data
            $data = new PaymentReject();
            $data->razerpay_order_id = $request->razorpayOrderId;
            $data->razerpay_payment_id = $request->razorpayPaymentId;
            $data->razerpay_signature = $request->razorpaySignature;
            // $data->save();   //<----------- here (RECHECK)
            return responseMsg(false, "Faild operation!", $error);
        } catch (Exception $exception) {
            return responseMsg(false, "Operation Error", $exception->getMessage());
        }
    }
}
