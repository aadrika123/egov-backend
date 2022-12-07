<?php

namespace App\Traits\Payment;

use App\Models\Payment\PaymentRequest;
use App\Models\Payment\PaymentReject;
use App\Models\Payment\PaymentSuccess;
use Exception;
use Razorpay\Api\Api;
use Illuminate\Support\Str;
use Razorpay\Api\Errors\SignatureVerificationError;
use App\Http\Controllers\NewPdfController; //<----------traits
use App\Models\Payment;
use App\Models\Payment\CardDetail;
use App\Models\Payment\WebhookPaymentData;
use App\Repository\Property\Concrete\SafRepository;
use App\Repository\Trade\Trade;
use App\Repository\WorkflowMaster\Concrete\WorkflowMap;
use Carbon\Carbon;
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
    private $refRazorpayId = "rzp_test_3MPOKRI8WOd54p";
    private $refRazorpayKey = "k23OSfMevkBszuPY5ZtZwutU";

    /**
     * | code : Sam Kerketta
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
     * | Time : 
     */

    public function saveGenerateOrderid($request)
    {
        try {
            $mUserID = auth()->user()->id;
            $mUlbID = auth()->user()->ulb_id;

            $mReciptId = Str::random(10);                                           //<--------- here (STATIC)

            $mApi = new Api($this->refRazorpayId, $this->refRazorpayKey);           //<--------- here (CAUTION)
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
                'departmentId' => $request->departmentId                              //<-----here(CHECK)

            ];

            $transaction = new PaymentRequest();
            $transaction->user_id = $mUserID;
            $transaction->workflow_id = $request->workflowId;
            $transaction->ulb_id = $mUlbID;
            $transaction->application_id = $request->id;
            $transaction->department_id = $request->departmentId;                       //<--------here(CHECK)
            $transaction->razorpay_order_id = $mReturndata['orderId'];
            $transaction->amount = $request->amount;
            $transaction->currency = 'INR';
            $transaction->save();                                                       

            return $mReturndata;                                                        //<------------------ here(RESPONSE)
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
     * | this -> naming
     * | here -> variable
     */
    function paymentVerify($request, $attributes)
    {
        try {
            $success = false;

            # verify the existence of the razerpay Id
            if (!null == ($request->razorpayPaymentId) && !empty($request->razorpaySignature)) {
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
            if ($success === true) {
                # Update database with success data
                try {
                    $successData = new PaymentSuccess();
                    $successData->razerpay_order_id = $request->razorpayOrderId;
                    $successData->razerpay_payment_id = $request->razorpayPaymentId;
                    $successData->razerpay_signature = $request->razorpaySignature;
                    $successData->save();

                    return responseMsg(true, "Payment Success!", "");
                } catch (Exception $exception) {
                    return responseMsg(false, "Error listed below", $exception->getMessage());
                }
            }

            # Update database with error data
            $rejectData = new PaymentReject();
            $rejectData->razerpay_order_id = $request->razorpayOrderId;
            $rejectData->razerpay_payment_id = $request->razorpayPaymentId;
            if (!empty($request->razorpaySignature)) {
                $rejectData->suspecious = true;
            }
            $rejectData->razerpay_signature = $request->razorpaySignature;
            $rejectData->reason = $request->reason;
            $rejectData->source = $request->source;
            $rejectData->step = $request->step;
            $rejectData->code = $request->code;
            $rejectData->description = $request->description;
            $rejectData->save();

            return responseMsg(true, "Failer data saved", $error);
        } catch (Exception $exception) {
            return responseMsg(false, "Exception occured of whole function", $exception->getMessage());
        }
    }

    // the integration of the webhook
    /**
     * | ----------------- verification of the signature ------------------------------- |
     * | @var dataOfRequest 
     * | @var accountId
     * | @var aCard
     * | @var card
     * | @var something
     * | @var notes
     * | @var arrayInAquirer
     * | @var firstKey
     * | @var save
     * | @var obj
     * | @var amount
     * | @var emai
     * | @var phone
     * | @var url
     * | @var token
     * | @param request
     * | Operation : this function url is hited by the webhook and the detail of the payment is collected in request 
     *               thie the storage -> generating pdf -> generating json ->save -> hitting url for watsapp message.
     * | Rating : 4
     * | this -> naming
     * | here -> variable
     */
    public function collectWebhookDetails($request)
    {
        # Variable Defining Section
        $dataOfRequest = $request->all();

        #contains
        $contains = json_encode($request->contains);

        #notes
        $notes = json_encode($request->payload['payment']['entity']['notes']);
        $depatmentId = $request->payload['payment']['entity']['notes']['departmentId'];
       
        #amount/ actualAmount
        $amount = $request->payload['payment']['entity']['amount'];
        $actulaAmount = $amount / 100;
        
        #accquireData/ Its key Valaue
        $arrayInAquirer = $dataOfRequest['payload']['payment']['entity']['acquirer_data'];
        $firstKey = array_key_first($arrayInAquirer);

        #status
        $status = $request->payload['payment']['entity']['status'];

        #captured
        $captured = $request->payload['payment']['entity']['captured'];

        #transaction Details
        $transTransferDetails['paymentId'] = $request->payload['payment']['entity']['id'];
        $transTransferDetails['orderId'] = $request->payload['payment']['entity']['order_id'];
        $transTransferDetails['status'] = $status;

        #data to be saved in card detail table                                                                           //<----------here
        $aCard = $request->payload['payment']['entity']['card_id'];
        if (!is_null($aCard)) {
            $card = new CardDetail();
            $card->id               = $request->payload['payment']['entity']['card']['id'];
            $card->entity           = $request->payload['payment']['entity']['card']['entity'];
            $card->name             = $request->payload['payment']['entity']['card']['name'];
            $card->last4            = $request->payload['payment']['entity']['card']['last4'];
            $card->network          = $request->payload['payment']['entity']['card']['network'];
            $card->type             = $request->payload['payment']['entity']['card']['type'];
            $card->issuer           = $request->payload['payment']['entity']['card']['issuer'];
            $card->international    = $request->payload['payment']['entity']['card']['international'];
            $card->emi              = $request->payload['payment']['entity']['card']['emi'];
            $card->sub_type         = $request->payload['payment']['entity']['card']['sub_type'];

            $card->save();
        }

        # data to be stored in the database       
        $data = new WebhookPaymentData();
        $data->entity                       = $request->entity;
        $data->account_id                   = $request->account_id;
        $data->event                        = $request->event;
        $data->contains                     = $contains;                                                    //<---------- this(CONTAINS)
        $data->payment_id                   = $request->payload['payment']['entity']['id'];
        $data->payment_entity               = $request->payload['payment']['entity']['entity'];
        $data->payment_amount               = $actulaAmount;                                                //<-------- here
        $data->payment_currency             = $request->payload['payment']['entity']['currency'];
        $data->payment_status               = $status;                                                      //<---------------- here (STATUS)
        $data->payment_order_id             = $request->payload['payment']['entity']['order_id'];
        $data->payment_invoice_id           = $request->payload['payment']['entity']['invoice_id'];
        $data->payment_international        = $request->payload['payment']['entity']['international'];
        $data->payment_method               = $request->payload['payment']['entity']['method'];
        $data->payment_amount_refunded      = $request->payload['payment']['entity']['amount_refunded'];
        $data->payment_refund_status        = $request->payload['payment']['entity']['refund_status'];
        $data->payment_captured             = $captured;
        $data->payment_description          = $request->payload['payment']['entity']['description'];
        $data->payment_card_id              = $request->payload['payment']['entity']['card_id'];
        // $data->card_detail_id               = $card->cid;                                                //<----------- this(EXCEPTION)
        $data->payment_bank                 = $request->payload['payment']['entity']['bank'];
        $data->payment_wallet               = $request->payload['payment']['entity']['wallet'];
        $data->payment_vpa                  = $request->payload['payment']['entity']['vpa'];
        $data->payment_email                = $request->payload['payment']['entity']['email'];
        $data->payment_contact              = $request->payload['payment']['entity']['contact'];
        $data->payment_notes                = $notes;                                                       //<-----here (NOTES)
        $data->payment_fee                  = $request->payload['payment']['entity']['fee'];
        $data->payment_tax                  = $request->payload['payment']['entity']['tax'];
        $data->payment_error_code           = $request->payload['payment']['entity']['error_code'];
        $data->payment_error_description    = $request->payload['payment']['entity']['error_description'];
        $data->payment_error_source         = $request->payload['payment']['entity']['error_source'];
        $data->payment_error_step           = $request->payload['payment']['entity']['error_step'];
        $data->payment_error_reason         = $request->payload['payment']['entity']['error_reason'];
        $data->payment_acquirer_data_type   = $firstKey;                                                    //<------------here (FIRSTKEY)
        $data->payment_acquirer_data_value  = $request->payload['payment']['entity']['acquirer_data'][$firstKey];
        $data->payment_created_at           = $request->payload['payment']['entity']['created_at'];
        $data->webhook_created_at           = $request->created_at;
        $data->user_id                      = $request->payload['payment']['entity']['notes']['userId'];
        $data->department_id                = $request->payload['payment']['entity']['notes']['departmentId'];
        $data->workflow_id                  = $request->payload['payment']['entity']['notes']['workflowId'];
        $data->ulb_id                       = $request->payload['payment']['entity']['notes']['ulbId'];
       # transaction id generation and saving
        $actualTransactionNo = $this->generatingTransactionId($transTransferDetails);
        $data->payment_transaction_id = $actualTransactionNo;
        $data->save();

        # property data transfer to the respective module dataBase 
        $transfer['paymentMode'] = $data->payment_method;
        $transfer['id'] = $request->payload['payment']['entity']['notes']['applicationId'];
        $transfer['amount'] = $actulaAmount;
        $transfer['workflowId'] = $request->payload['payment']['entity']['notes']['workflowId'];
        $transfer['transactionNo'] = $actualTransactionNo;

        # conditionaly upadting the request data
        if ($status == 'captured' && $captured == 1) {
            PaymentRequest::where('razorpay_order_id', $request->payload['payment']['entity']['order_id'])
                ->update(['payment_status' => 1]);

            # calling function for the modules   //<------------ here (Call Another Function)
            switch ($depatmentId) {
                case ('1'):
                    $obj = new SafRepository();
                    $obj->paymentSaf($transfer);
                    break;
                case('3'):
                    $objTrade = new Trade();
                    $objTrade->handeRazorPay($transfer);
                    break;    
                default:
                    // $msg = 'Something went wrong on switch';
            }
        }                                                                                    //<------------------ here (CAUTION)
        return responseMsg(true, "Webhook Data Collected!", $request->event);
    }


    /**
     * | ----------------- generating Application ID ------------------------------- |
     * | @param request
     * | @param error
     * | @var id
     * | @return transactionNo
     * | Operation : this function generate a random and unique transactionID
     * | Rating : 1
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
