<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterTransactionDeactivateDtl extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamps = false;
    protected $connection = 'pgsql_water';

    public function getDetails($fromDate, $uptoDate)
    {
        return self::select(
            ''
        )
            ->join('water_transactions as wt', 'wt.id', 'water_transaction_deactivate_dtls.tran_id')
            ->join("users as u", "u.id", "wt.deactivate_by")
            ->where("water_transaction_deactivate_dtls.deactivate_date", '>=', $fromDate)
            ->where("water_transaction_deactivate_dtls.deactivate_date", '<=', $uptoDate);
    }
}
