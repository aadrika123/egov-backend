<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeTransaction extends Model
{
    use HasFactory;
    public $timestamps = false;

    public static function listByLicId($licenseId)
    {
        return self::select("*")
            ->where("temp_id", $licenseId)
            ->whereIn("status", [1, 2])
            ->get();
    }

    public static function chequeTranDtl($ulbId)
    {
        return  TradeTransaction::select(
            'trade_cheque_dtls.*',
            'tran_date',
            'tran_no',
            'payment_mode',
            'paid_amount',
            "cheque_date",
            "bank_name",
            "branch_name",
            "state",
            "cheque_no",
            "clear_bounce_date",
        )
            ->leftjoin('trade_cheque_dtls', 'trade_cheque_dtls.tran_id', 'trade_transactions.id')
            ->where('payment_mode', 'DD')
            ->orWhere('payment_mode', 'CHEQUE')
            ->where('ulb_id', $ulbId);
    }
}
