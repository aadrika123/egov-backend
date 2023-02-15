<?php

namespace App\Models\Water;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterTranDetail extends Model
{
    use HasFactory;
    public $timestamps = false;

    /**
     * | get Transaction Detail By transId
     * | @param tranId
     */
    public function getDetailByTranId($tranId)
    {
        return WaterTranDetail::where('tran_id', $tranId)
            ->where('status', true)
            ->firstorFail();
    }

    /**
     * | Savr
     */
    public function saveDefaultTrans($totalConnectionCharges, $applicationId, $transactionId, $connectionId)
    {
        $TradeDtl = new WaterTranDetail;
        $TradeDtl->tran_id          = $transactionId;
        $TradeDtl->demand_id        = $connectionId;
        $TradeDtl->total_demand     = $totalConnectionCharges;
        $TradeDtl->application_id   = $applicationId;
        $TradeDtl->created_at       = Carbon::now();
        $TradeDtl->save();
    }
}
