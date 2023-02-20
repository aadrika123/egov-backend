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
    public function saveWaterPenelty($applicationId, $installments)
    {
        $quaters = new WaterPenaltyInstallment();
        $quaters->apply_connection_id = $applicationId;
        $quaters->installment_amount = $installments['installment_amount'];
        $quaters->penalty_head = $installments['penalty_head'];
        $quaters->balance_amount = $installments['balance_amount'];
        $quaters->save();
    }

    /**
     * |------------- Delete the Penelty Installment -------------------|
        | Soft Deleta the data
     */
    public function deleteWaterPenelty($applicationId)
    {
        $waterPenelty = WaterPenaltyInstallment::where('apply_connection_id', $applicationId)
            ->get();
        if ($waterPenelty) {
            WaterPenaltyInstallment::where('apply_connection_id', $applicationId)
                ->delete();
        }
    }


    /**
     * | Get the penalty installment according to application Id
     * | @param applicationId
     */
    public function getPenaltyByApplicationId($applicationId)
    {
        return WaterPenaltyInstallment::where('apply_connection_id', $applicationId)
            ->where('status', 1);
    }


    /**
     * | Deactivate the water penalty charges
     * | @param request
     */
    public function deactivatePenalty($applicationId)
    {
        WaterPenaltyInstallment::where('apply_connection_id', $applicationId)
            ->update([
                'status' => false
            ]);
    }
}
