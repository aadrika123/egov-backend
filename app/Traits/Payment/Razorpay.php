<?php

namespace App\Traits\Payment;

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
use App\Repository\Trade\Trade;
use App\Repository\Water\Concrete\WaterNewConnection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
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
     */

    public function saveGenerateOrderid($request)
    {
        try {
            $mUserID = auth()->user()->id;
            $mUlbID = $request["ulb_id"];                                           // user Id
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

            $mReturndata = [
                'orderId' => $mOrder['id'],
                'amount' => $request->all()['amount'],
                'currency' => 'INR',
                'userId' => $mUserID,
                'ulbId' => $mUlbID,
                'workflowId' => $request->workflowId,
                'applicationId' => $request->id,
                'departmentId' => $request->departmentId

            ];

            $saveRequestObj = new PaymentRequest();
            $saveRequestObj->saveRazorpayRequest($mUserID, $mUlbID, $mReturndata['orderId'], $request);

            return $mReturndata;
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
                    return responseMsg(true, "Payment Success!", "");
                } catch (Exception $exception) {
                    return responseMsg(false, "Error listed below!", $exception->getMessage());
                }
            }
            # Update database with error data
            $rejectData = new PaymentReject();
            $rejectData->saveRejectedData($request);
            return responseMsg(true, "Failer data saved!", $error);
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
    public function collectWebhookDetails($request)
    {
        // try {
        # Variable Defining Section
        $webhookEntity=$request->payload['payment']['entity'];

        #contains
        $contains = json_encode($request->contains);

        #notes
        $notes = json_encode($webhookEntity['notes']);
        $depatmentId = $webhookEntity['notes']['departmentId'];

        #amount/ actualAmount
        $amount = $webhookEntity['amount'];
        $actulaAmount = $amount / 100;

        #accquireData/ Its key Valaue
        $arrayInAquirer = $webhookEntity['acquirer_data'];
        $firstKey = array_key_first($arrayInAquirer);

        #status
        $status = $webhookEntity['status'];

        #captured
        $captured = $webhookEntity['captured'];
        
        #transaction Details
        $transTransferDetails['paymentId'] = $webhookEntity['id'];
        $transTransferDetails['orderId'] = $webhookEntity['order_id'];
        $transTransferDetails['status'] = $status;

        #data to be saved in card detail table                                                                         
        $aCard = $webhookEntity['card_id'];
        if (!is_null($aCard)) {

            $webhookCardDetails= $webhookEntity['card'];
            $card = new CardDetail();
            $card->saveCardDetails($webhookCardDetails);
           
        }

        # data to be stored in the database 
             
        $webhookData = new WebhookPaymentData();
        $webhookData->entity                       = $request->entity;
        $webhookData->account_id                   = $request->account_id;
        $webhookData->event                        = $request->event;
        $webhookData->webhook_created_at           = $request->created_at;
        $webhookData->payment_captured             = $captured;
        $webhookData->payment_amount               = $actulaAmount;  
        $webhookData->payment_status               = $status;                                                      //<---------------- here (STATUS)
        $webhookData->payment_notes                = $notes;                                                       //<-----here (NOTES)
        $webhookData->payment_acquirer_data_type   = $firstKey;                                                    //<------------here (FIRSTKEY)
        $webhookData->contains                     = $contains;                                                    //<---------- this(CONTAINS)
        $webhookData->payment_id                   = $webhookEntity['id'];
        $webhookData->payment_entity               = $webhookEntity['entity'];                                               
        $webhookData->payment_currency             = $webhookEntity['currency'];                                                     
        $webhookData->payment_order_id             = $webhookEntity['order_id'];
        $webhookData->payment_invoice_id           = $webhookEntity['invoice_id'];
        $webhookData->payment_international        = $webhookEntity['international'];
        $webhookData->payment_method               = $webhookEntity['method'];
        $webhookData->payment_amount_refunded      = $webhookEntity['amount_refunded'];
        $webhookData->payment_refund_status        = $webhookEntity['refund_status'];
        $webhookData->payment_description          = $webhookEntity['description'];
        $webhookData->payment_card_id              = $webhookEntity['card_id'];
        $webhookData->payment_bank                 = $webhookEntity['bank'];
        $webhookData->payment_wallet               = $webhookEntity['wallet'];
        $webhookData->payment_vpa                  = $webhookEntity['vpa'];
        $webhookData->payment_email                = $webhookEntity['email'];
        $webhookData->payment_contact              = $webhookEntity['contact'];                                                   
        $webhookData->payment_fee                  = $webhookEntity['fee'];
        $webhookData->payment_tax                  = $webhookEntity['tax'];
        $webhookData->payment_error_code           = $webhookEntity['error_code'];
        $webhookData->payment_error_description    = $webhookEntity['error_description'];
        $webhookData->payment_error_source         = $webhookEntity['error_source'] ?? null;
        $webhookData->payment_error_step           = $webhookEntity['error_step'] ?? null;
        $webhookData->payment_error_reason         = $webhookEntity['error_reason'] ?? null;                                              
        $webhookData->payment_acquirer_data_value  = $webhookEntity['acquirer_data'][$firstKey];
        $webhookData->payment_created_at           = $webhookEntity['created_at'];

        # user details
        $webhookData->user_id                      = $webhookEntity['notes']['userId'];
        $webhookData->department_id                = $webhookEntity['notes']['departmentId'];
        $webhookData->workflow_id                  = $webhookEntity['notes']['workflowId'];
        $webhookData->ulb_id                       = $webhookEntity['notes']['ulbId'];

        # transaction id generation and saving
        $actualTransactionNo = $this->generatingTransactionId($transTransferDetails);
        $webhookData->payment_transaction_id = $actualTransactionNo;
        $webhookData->save();

        # data transfer to the respective module dataBase 
        $transfer['paymentMode'] = $webhookData->payment_method;
        $transfer['id'] = $webhookEntity['notes']['applicationId'];
        $transfer['amount'] = $actulaAmount;
        $transfer['workflowId'] =  $webhookData->workflow_id;
        $transfer['transactionNo'] = $actualTransactionNo;
        $transfer['userId'] = $webhookData->user_id;
        $transfer['ulbId'] = $webhookData->ulb_id;
        $transfer['departmentId'] = $webhookData->department_id;
        $transfer['orderId'] = $webhookData->payment_order_id;
        $transfer['paymentId'] = $webhookData->payment_id;

        # conditionaly upadting the request data
        if ($status == 'captured' && $captured == 1) {
            PaymentRequest::where('razorpay_order_id', $webhookEntity['order_id'])
                ->update(['payment_status' => 1]);

            # calling function for the modules                  
            switch ($depatmentId) {
                case ('1'):                                      //<------------------ (SAF PAYMENT)
                    $obj = new SafRepository();
                    $obj->paymentSaf($transfer);
                    break;
                case ('2'):                                      //<-------------------(Water)
                    $objWater = new WaterNewConnection();
                    $objWater->razorPayResponse($transfer);
                    break;
                case ('3'):                                      //<-------------------(TRADE)
                    $objTrade = new Trade();
                    $objTrade->razorPayResponse($transfer);
                    break;
                default:
                    // $msg = 'Something went wrong on switch';
            }
        }
        return responseMsg(true, "Webhook Data Collected!", $request->event);
        // } catch (Exception $e) {
        //     print_r($e->getfile(), $e->getMessage());
        //     return responseMsg(false, "error occured", $e->getLine());
        // }
    }


    /**
     * | ----------------- generating Application ID ------------------------------- |
     * | @param request
     * | @param error
     * | @var id
     * | @return transactionNo
     * | Operation : this function generate a random and unique transactionID
     * | Rating : 1
        | Serial No : 04
     */
    public function generatingTransactionId($request)
    {
        try {
            $id = WebhookPaymentData::select('id')
                ->where('payment_id', $request['paymentId'])
                ->where('payment_order_id', $request['orderId'])
                ->where('payment_status', $request['status'])
                ->get()
                ->first();
            return Carbon::createFromDate()->milli . $id->id . carbon::now()->diffInMicroseconds();
        } catch (Exception $error) {
            return response()->json([$error->getMessage()]);
        }
    }
}
