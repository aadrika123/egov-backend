<?php

namespace App\Models\Water;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterConsumerTax extends Model
{
    use HasFactory;

    /**
     * | Save the Consumer Tax Details 
     * | @param 
     */
    public function saveConsumerTax($taxDetails, $consumerDetails)
    {
        $mWaterConsumerTax = new WaterConsumerTax();
        $mWaterConsumerTax->ward_mstr_id     = $consumerDetails['ward_mstr_id'];
        $mWaterConsumerTax->consumer_id      = $consumerDetails['id'];
        $mWaterConsumerTax->charge_type      = $taxDetails['charge_type'];
        $mWaterConsumerTax->rate_id          = $taxDetails['rate_id'];
        $mWaterConsumerTax->initial_reading  = $taxDetails['initial_reading'];
        $mWaterConsumerTax->final_reading    = $taxDetails['final_reading'];
        $mWaterConsumerTax->amount           = $taxDetails['amount'];
        $mWaterConsumerTax->effective_from   = $taxDetails['effective_from'];
        $mWaterConsumerTax->emp_details_id   = authUser()->id;
        $mWaterConsumerTax->created_on       = Carbon::now();
        $mWaterConsumerTax->save();
        return $mWaterConsumerTax->id;
    }
}
