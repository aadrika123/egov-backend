<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterPenaltyInstallment extends Model
{
    use HasFactory;

    /**
     * |----------------------------- Save new water -----------------------------|
     * | @param 
     * | @var
        |
     */
    public function saveWaterPenelty($applicationId,$installments)
    {
        $quaters= new WaterPenaltyInstallment();
        $quaters->apply_connection_id = $applicationId;
        $quaters->installment_amount = $installments['installment_amount'];
        $quaters->penalty_head = $installments['penalty_head'];
        $quaters->balance_amount = $installments['balance_amount'];
        $quaters->save();
    }
}
