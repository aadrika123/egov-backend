<?php

namespace App\Models\Water;

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
}
