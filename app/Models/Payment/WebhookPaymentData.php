<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookPaymentData extends Model
{
    use HasFactory;

    /**
     * |---------------------------------- gtting the notes details from webhook for the user Id ------------------------------|
     * | @param request
     * | @var details
        | Serial No : 
     */
    public function getNotesDetails($request)
    {
        $details = WebhookPaymentData::select(
            'payment_transaction_id AS transactionNo',
            'created_at AS dateOfTransaction',
            'payment_method AS paymentMethod',
            'payment_amount AS amount',
            'payment_status AS paymentStatus'
        )
            ->where('user_id', $request)
            ->get();
        if (!empty($details['0'])) {
            return $details;
        }
        return ("no data!");
    }


    /**
     * |------------------------------- Get ApplicationId -------------------------------------|
     * | @param payId : paymentId
     * | @var userDetails
     * | @return data
        | Serial No :
     */
    public function getApplicationId($payId)
    {
        $userDetails = WebhookPaymentData::where('payment_id', $payId)
            ->select(
                'payment_notes AS userDetails'
            )
            ->get();
        $data = collect($userDetails)->first()->userDetails;
        return $data;
    }


    /**
     * |----------------------------------- Save Webhook data / Razorpay ----------------------------------|
     * | @param request
     * | @param captured
     * | @param actulaAmount
     * | @param status
     * | @param notes
     * | @param firstKey
     * | @param contains
     * | @param actualTransactionNo
     * | @param webhookEntity
     * | @var webhookData : Object for the model 
        | Serial No : 
     */
    public function saveWebhookData($request,$captured,$actulaAmount,$status,$notes,$firstKey,$contains,$actualTransactionNo,$webhookEntity)
    {
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
        $webhookData->department_id                = $webhookEntity['notes']['departmentId'];   // moduleId
        $webhookData->workflow_id                  = $webhookEntity['notes']['workflowId'];
        $webhookData->ulb_id                       = $webhookEntity['notes']['ulbId'];

        # transaction id generation and saving
        $webhookData->payment_transaction_id = $actualTransactionNo;
        $webhookData->save();

        return $webhookData;
    }


    /**
     * |----------------------------------- Get Webhook Details By transactionId --------------------------------|
     * | @param req : transaction Id
     * 
     */
    public function webhookByTransaction($req)
    {
       return WebhookPaymentData::select(
            'payment_order_id AS orderId',
            'payment_amount AS amount',
            'payment_status AS status',
            'payment_bank AS bank',
            'payment_contact AS contact',
            'payment_method AS method',
            'payment_id AS paymentId',
            'payment_transaction_id AS transactionNo',
            'payment_acquirer_data_value AS paymentAcquirerDataValue',
            'payment_acquirer_data_type AS paymentAcquirerDataType',
            'payment_error_reason AS paymentErrorReason',
            'payment_error_source AS paymentErrorSource',
            'payment_error_description AS paymentErrorDescription',
            'payment_error_code AS paymentErrorCode',
            'payment_email AS emails',
            'payment_vpa AS  paymentVpa',
            'payment_wallet AS paymentWallet',
            'payment_card_id AS paymentCardId'
        )
            ->where('payment_transaction_id', $req->transactionNo)
            ->orderByDesc('id');
    }


    /**
     * 
     */
    public function getPaymentDetailsByPId ($payId)
    {
        return WebhookPaymentData::where('payment_id', $payId)
            ->get();
    }
}
