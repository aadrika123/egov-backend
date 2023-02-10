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
}
