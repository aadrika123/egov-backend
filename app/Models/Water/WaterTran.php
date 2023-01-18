<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterTran extends Model
{
    use HasFactory;
    public $timestamps=false;

    /**
     * |--------------- Get Transaction Data -----------|
     */
    public function getTransactionDetailsById($req)
    {
        return WaterTran::where('related_id',$req)
        ->get();
    }

    /**
     * |---------------- Get transaction by the transaction details ---------------|
     */
    public function getTransNo($applicationId,$applicationFor)
    {
        return WaterTran::where('related_id',$applicationId)
        ->where('tran_type',$applicationFor);
    }
}
