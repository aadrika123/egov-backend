<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterTranDetail extends Model
{
    use HasFactory;
    public $timestamps=false;

    /**
     * | get Transaction Detail By transId
     * | @param tranId
     */
    public function getDetailByTranId($tranId)
    {
        return WaterTranDetail::where('tran_id',$tranId)
        ->where('status',true)
        ->firstorFail();
    }
}
