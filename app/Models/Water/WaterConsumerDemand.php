<?php

namespace App\Models\Water;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterConsumerDemand extends Model
{
    use HasFactory;
    /**
     * | Get Payed Consumer Demand
     * | @param ConsumerId
     */
    public function getDemandBydemandId($demandId)
    {
        return WaterConsumerDemand::where('id', $demandId)
            ->where('paid_status', true)
            ->first();
    }


    /**
     * | Get Demand According to consumerId and payment status false 
     */
    public function getConsumerDemand($consumerId)
    {
        return WaterConsumerDemand::where('consumer_id', $consumerId)
            ->where('paid_status', false)
            ->where('status', true)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * | Deactivate the consumer Demand
     * | Demand Ids will be in array
     * | @param DemandIds
     */
    public function deactivateDemand($demandIds)
    {
        WaterConsumerDemand::whereIn('id', $demandIds)
            ->update([
                'status' => false
            ]);
    }


    /**
     * | Save the consumer demand while Demand generation
     * | @param demands
     * | @param meterDetails
     */
    public function saveConsumerDemand($demands, $meterDetails, $consumerDetails, $request, $taxId)
    {
        $mWaterConsumerDemand = new WaterConsumerDemand();
        $mWaterConsumerDemand->consumer_id              =  $consumerDetails->id;
        $mWaterConsumerDemand->ward_id                  =  $consumerDetails->ward_mstr_id;
        $mWaterConsumerDemand->generation_date          =  $demands['generation_date'];
        $mWaterConsumerDemand->amount                   =  $demands['amount'];
        $mWaterConsumerDemand->paid_status              =  false;
        $mWaterConsumerDemand->consumer_tax_id          =  $taxId;
        $mWaterConsumerDemand->emp_details_id           =  authUser()->id;
        $mWaterConsumerDemand->demand_from              =  $demands['demand_from'];
        $mWaterConsumerDemand->demand_upto              =  $demands['demand_upto'];
        $mWaterConsumerDemand->penalty                  =  $demands['penalty'] ?? 0;
        $mWaterConsumerDemand->current_meter_reading    =  $request->finalRading;
        $mWaterConsumerDemand->unit_amount              =  $demands['unit_amount'];
        $mWaterConsumerDemand->connection_type          =  $meterDetails['charge_type'];
        $mWaterConsumerDemand->demand_no                =  "RMC" . random_int(100000, 999999) . "/" . random_int(1, 10);
        $mWaterConsumerDemand->balance_amount           =  $demands['penalty'] ?? 0 + $demands['amount'];
        $mWaterConsumerDemand->created_at               =  Carbon::now();
        $mWaterConsumerDemand->save();
    }


    /**
     * | Get Demand According to consumerId and payment status false 
     */
    public function getFirstConsumerDemand($consumerId)
    {
        return WaterConsumerDemand::where('consumer_id', $consumerId)
            ->where('paid_status', false)
            ->where('status', true)
            ->orderByDesc('id');
    }
}
