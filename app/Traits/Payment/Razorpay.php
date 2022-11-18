<?php

namespace App\Traits\Payment;

use App\Models\Payment\PaymentRequest;
use App\Models\Payment\PaymentReject;
use App\Models\Payment\PaymentSuccess;
use Exception;
use Razorpay\Api\Api;
use Illuminate\Support\Str;
use Razorpay\Api\Errors\SignatureVerificationError;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\NewPdfController; //<----------traits
use App\Models\Payment\CardDetail;
use App\Models\Payment\WebhookPaymentData;
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
            # validation complete and the storing of data
            if ($success === true) {
                # Update database with success data
                try {
                    $data = new PaymentSuccess();
                    $data->razerpay_order_id = $request->razorpayOrderId;
                    $data->razerpay_payment_id = $request->razorpayPaymentId;
                    $data->razerpay_signature = $request->razorpaySignature;
                    $data->save();  //<----------- here (RECHECK)

                    return responseMsg(true, "Payment Success!", "");
                } catch (Exception $exception) {
                    return responseMsg(false, "Error listed below", $exception->getMessage());
                }
            }

            # Update database with error data
            $data = new PaymentReject();
            $data->razerpay_order_id = $request->razorpayOrderId;
            $data->razerpay_payment_id = $request->razorpayPaymentId;
            if (!empty($request->razorpaySignature)) {
                $data->suspecious = true;
            }
            $data->razerpay_signature = $request->razorpaySignature;
            $data->reason = $request->reason;
            $data->source = $request->source;
            $data->step = $request->step;
            $data->code = $request->code;
            $data->description = $request->description;

            $data->save();   //<----------- here (RECHECK)
            return responseMsg(true, "Faild data saved", $error);
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
     * | this -> naming
     * | here -> variable
     */
    public function collectWebhookDetails($request)
    {
        try {
            #collecting all data
            $dataOfRequest = $request->all();

            # data of the contains from request
            $contains = json_encode($request->contains);

            # data of notes from request
            $notes = json_encode($request->payload['payment']['entity']['notes']);

            # key name of the aquired data 
            $arrayInAquirer = $dataOfRequest['payload']['payment']['entity']['acquirer_data'];
            $firstKey = array_key_first($arrayInAquirer);


            DB::beginTransaction(); //<----------here
            #   data to be saved in card detail table
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
            $data->contains                     = $contains; //<---------- this(CONTAINS)
            $data->payment_id                   = $request->payload['payment']['entity']['id'];
            $data->payment_entity               = $request->payload['payment']['entity']['entity'];
            $data->payment_amount               = $request->payload['payment']['entity']['amount'];
            $data->payment_currency             = $request->payload['payment']['entity']['currency'];
            $data->payment_status               = $request->payload['payment']['entity']['status'];
            $data->payment_order_id             = $request->payload['payment']['entity']['order_id'];
            $data->payment_invoice_id           = $request->payload['payment']['entity']['invoice_id'];
            $data->payment_international        = $request->payload['payment']['entity']['international'];
            $data->payment_method               = $request->payload['payment']['entity']['method'];
            $data->payment_amount_refunded      = $request->payload['payment']['entity']['amount_refunded'];
            $data->payment_refund_status        = $request->payload['payment']['entity']['refund_status'];
            $data->payment_captured             = $request->payload['payment']['entity']['captured'];
            $data->payment_description          = $request->payload['payment']['entity']['description'];
            $data->payment_card_id              = $request->payload['payment']['entity']['card_id'];
            // $data->card_detail_id               = $card->cid; //<----------- this(EXCEPTION)
            $data->payment_bank                 = $request->payload['payment']['entity']['bank'];
            $data->payment_wallet               = $request->payload['payment']['entity']['wallet'];
            $data->payment_vpa                  = $request->payload['payment']['entity']['vpa'];
            $data->payment_email                = $request->payload['payment']['entity']['email'];
            $data->payment_contact              = $request->payload['payment']['entity']['contact'];
            $data->payment_notes                = $notes;  //<-----here (NOTES)
            $data->payment_fee                  = $request->payload['payment']['entity']['fee'];
            $data->payment_tax                  = $request->payload['payment']['entity']['tax'];
            $data->payment_error_code           = $request->payload['payment']['entity']['error_code'];
            $data->payment_error_description    = $request->payload['payment']['entity']['error_description'];
            $data->payment_error_source         = $request->payload['payment']['entity']['error_source'];
            $data->payment_error_step           = $request->payload['payment']['entity']['error_step'];
            $data->payment_error_reason         = $request->payload['payment']['entity']['error_reason'];
            $data->payment_acquirer_data_type   = $firstKey;    //<------------here (FIRSTKEY)
            $data->payment_acquirer_data_value  = $request->payload['payment']['entity']['acquirer_data'][$firstKey];
            $data->payment_created_at           = $request->payload['payment']['entity']['created_at'];
            $data->webhook_created_at          = $request->created_at;

            $data->save();
            DB::commit(); //<------------------ here (CAUTION)
            return responseMsg(true, "Webhook Data Collected!", $request->event);
        } catch (Exception $error) {
            return responseMsg(true, "ERROR LISTED BELOW!", $error->getMessage());
        }
    }
}
