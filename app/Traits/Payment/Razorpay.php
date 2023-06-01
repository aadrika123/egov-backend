<?php

namespace App\Traits\Payment;

use App\Http\Controllers\Property\ActiveSafController;
use App\Models\Payment\PaymentRequest;
use App\Models\Payment\PaymentReject;
use App\Models\Payment\PaymentSuccess;
use Exception;
use Razorpay\Api\Api;
use Illuminate\Support\Str;
use Razorpay\Api\Errors\SignatureVerificationError;
use App\Models\Payment\CardDetail;
use App\Models\Payment\WebhookPaymentData;
use App\Repository\Property\Concrete\SafRepository;
use App\Repository\Property\Interfaces\iSafRepository;
use App\Repository\Trade\Trade;
use App\Repository\Water\Concrete\WaterNewConnection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Trait for Razorpay
 * Created By-Sam kerktta
 * Created On-15-11-2022 
 * --------------------------------------------------------------------------------------
 */

trait Razorpay
{
    /**
     * | ----------------- payment generating order id / Saving in database ------------------------------- |
     * | @var mReciptId
     * | @var mUserID
     * | @var mUlbID
     * | @var mApi
     * | @var mOrder
     * | @var mReturndata
     * | @var transaction
     * | @var error
     * | @param request
     * | Operation : generating the order id according the request data using the razorpay API 
     * | Rating : 3
        | Serial No : 01
        | Department Id will be replaced by module Id /76
     */

    public function saveGenerateOrderid($request)
    {
        try {
            $mUserID = auth()->user()->id ?? $request->ghostUserId;
            $mUlbID = $request["ulbId"] ?? "2";                                            // user Id
            $refRazorpayId = Config::get('razorpay.RAZORPAY_ID');
            $refRazorpayKey = Config::get('razorpay.RAZORPAY_KEY');
            $mReciptId = Str::random(10);                                           // (STATIC) recipt ID

            $mApi = new Api($refRazorpayId, $refRazorpayKey);
            $mOrder = $mApi->order->create(array(
                'receipt' => $mReciptId,
                'amount' => $request->all()['amount'] * 100,
                'currency' => 'INR',
                'payment_capture' => 1
            ));

            $Returndata = [
                'orderId' => $mOrder['id'],
                'amount' => $request->all()['amount'],
                'currency' => 'INR',
                'userId' => $mUserID,
                'ulbId' => $mUlbID,
                'workflowId' => $request->workflowId,
                'applicationId' => $request->id,
                'departmentId' => $request->departmentId,
                'propType' => $request->propType
            ];

            $saveRequestObj = new PaymentRequest();
            $saveRequestObj->saveRazorpayRequest($mUserID, $mUlbID, $Returndata['orderId'], $request);
            return $Returndata;
        } catch (Exception $error) {
            return responseMsg(false, "Error Listed Below!", $error->getMessage());
        }
    }

    /**
     * | ----------------- verification of the signature ------------------------------- |
     * | @var reciptId
     * | @var api
     * | @var success
     * | @var successData
     * | @var rejectData
     * | @var error
     * | @param request
     * | @param attributes
     * |
     * | @constants : refRazorpayId
     * | @constants : refRazorpayKey
     * |
     * | Operation : generating the order id according the request data using the razorpay API 
     * | Rating : 4
        | Serial No : 02
        | (Working)
     */
    function paymentVerify($request, $attributes)
    {
        try {
            $success = false;
            $refRazorpayId = Config::get('razorpay.RAZORPAY_ID');
            $refRazorpayKey = Config::get('razorpay.RAZORPAY_KEY');

            # verify the existence of the razerpay Id
            if (!is_null($request->razorpayPaymentId) && !empty($request->razorpaySignature)) {
                $api = new Api($refRazorpayId, $refRazorpayKey);
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
            if ($success === true) {
                # Update database with success data
                try {
                    $successData = new PaymentSuccess();
                    $successData->saveSuccessDetails($request);
                    return responseMsg(true, "Payment Clear to Procede!", "");
                } catch (Exception $exception) {
                    return responseMsg(false, "Error listed below!", $exception->getMessage());
                }
            }
            # Update database with error data
            $rejectData = new PaymentReject();
            $rejectData->saveRejectedData($request);
            return responseMsg(true, "There is Some error!", $error);
        } catch (Exception $exception) {
            return responseMsg(false, "Exception occured of whole function", $exception->getMessage());
        }
    }



    /**
     * | -------------------------------- integration of the webhook ------------------------------- |
     * | @param request
     * | 
     * | @return 
     * | Operation : this function url is hited by the webhook and the detail of the payment is collected in request 
     *               thie the storage -> generating pdf -> generating json ->save -> hitting url for watsapp message.
     * | Rating : 4
     * | this -> naming
     * | here -> variable
        | Serial No : 03
        | Flag : department Id will be replaced / switch case / the checking of the payment is success (keys:amount,orderid,departmentid,status) / razorpay verification 
     */
    // public function collectWebhookDetails($request)
    // {
    //     try {
    //         # Variable Defining Section
    //         $webhookEntity = $request->payload['payment']['entity'];

    //         $contains = json_encode($request->contains);
    //         $notes = json_encode($webhookEntity['notes']);

    //         $depatmentId = $webhookEntity['notes']['departmentId']; // ModuleId
    //         $status = $webhookEntity['status'];
    //         $captured = $webhookEntity['captured'];
    //         $aCard = $webhookEntity['card_id'];
    //         $amount = $webhookEntity['amount'];
    //         $arrayInAquirer = $webhookEntity['acquirer_data'];

    //         $actulaAmount = $amount / 100;
    //         $firstKey = array_key_first($arrayInAquirer);
    //         $actualTransactionNo = $this->generatingTransactionId();

    //         if (!is_null($aCard)) {

    //             $webhookCardDetails = $webhookEntity['card'];
    //             $objcard = new CardDetail();
    //             $objcard->saveCardDetails($webhookCardDetails);
    //         }

    //         # data to be stored in the database 
    //         $webhookData = new WebhookPaymentData();
    //         $webhookData = $webhookData->saveWebhookData($request, $captured, $actulaAmount, $status, $notes, $firstKey, $contains, $actualTransactionNo, $webhookEntity);

    //         # data transfer to the respective module dataBase 
    //         $transfer['paymentMode'] = $webhookData->payment_method;
    //         $transfer['id'] = $webhookEntity['notes']['applicationId'];
    //         $transfer['amount'] = $actulaAmount;
    //         $transfer['workflowId'] =  $webhookData->workflow_id;
    //         $transfer['transactionNo'] = $actualTransactionNo;
    //         $transfer['userId'] = $webhookData->user_id;
    //         $transfer['ulbId'] = $webhookData->ulb_id;
    //         $transfer['departmentId'] = $webhookData->department_id;    //ModuleId
    //         $transfer['orderId'] = $webhookData->payment_order_id;
    //         $transfer['paymentId'] = $webhookData->payment_id;

    //         # conditionaly upadting the request data
    //         if ($status == 'captured' && $captured == 1) {
    //             PaymentRequest::where('razorpay_order_id', $webhookEntity['order_id'])
    //                 ->update(['payment_status' => 1]);

    //             # calling function for the modules                  
    //             switch ($depatmentId) {
    //                 case ('1'):      
    //                     $repo=new iSafRepository;                                //<------------------ (SAF PAYMENT)
    //                     $obj = new ActiveSafController($repo);
    //                     $obj->paymentSaf($transfer);
    //                     break;
    //                 case ('2'):                                      //<-------------------(Water)
    //                     $objWater = new WaterNewConnection();
    //                     $objWater->razorPayResponse($transfer);
    //                     break;
    //                 case ('3'):                                      //<-------------------(TRADE)
    //                     $objTrade = new Trade();
    //                     $objTrade->razorPayResponse($transfer);
    //                     break;
    //                 default:
    //                     // $msg = 'Something went wrong on switch';
    //             }
    //         }
    //         return responseMsg(true, "Webhook Data Collected!", $request->event);
    //     } catch (Exception $e) {
    //         return responseMsg(false, $e->getMessage(), $e->getLine());
    //     }
    // }



}
