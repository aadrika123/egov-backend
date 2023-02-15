<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterTran extends Model
{
    use HasFactory;
    public $timestamps = false;

    /**
     * |--------------- Get Transaction Data -----------|
     */
    public function getTransactionDetailsById($req)
    {
        return WaterTran::where('related_id', $req)
            ->get();
    }

    /**
     * |---------------- Get transaction by the transaction details ---------------|
     */
    public function getTransNo($applicationId, $applicationFor)
    {
        return WaterTran::where('related_id', $applicationId)
            ->where('tran_type', "<>", "Demand Collection")
            ->where('status', true);
    }
    public function ConsumerTransaction($applicationId)
    {
        return WaterTran::where('related_id', $applicationId)
            ->where('tran_type', "=", "Demand Collection")
            ->where('status', true);
    }

    /**
     * | Get Transaction Details According to TransactionId
     * | @param 
     */
    public function getTransactionByTransactionNo($transactionNo)
    {
        return WaterTran::select(
            'water_trans.*',
            'water_tran_details.demand_id'
        )
            ->join('water_tran_details', 'water_tran_details.tran_id', '=', 'water_trans.id')
            ->where('tran_no', $transactionNo)
            ->where('water_trans.status', true)
            ->where('water_tran_details.status', true)
            ->firstOrFail();
    }
}
