<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    /**
     * | Trade Transaction Details by date
     */
    public function tranDetail($date, $ulbId)
    {
        return TradeTransaction::select(
            'users.id',
            'users.user_name',
            DB::raw("sum(paid_amount) as amount"),
        )
            ->join('users', 'users.id', 'trade_transactions.emp_dtl_id')
            ->where('verify_date', $date)
            ->where('trade_transactions.status', 1)
            ->where('payment_mode', '!=', 'ONLINE')
            ->where('is_verified', true)
            ->where('trade_transactions.ulb_id', $ulbId)
            ->groupBy(["users.id", "users.user_name"]);
    }
}
