<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookPaymentData extends Model
{
    use HasFactory;

    // gtting the notes details from webhook for the user Id
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
}
