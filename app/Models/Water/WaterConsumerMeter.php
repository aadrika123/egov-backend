<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterConsumerMeter extends Model
{
    use HasFactory;

    /**
     * | Get Meter reading using the ConsumerId
     * | @param consumerId
     */
    public function getMeterDetailsByConsumerId($consumerId)
    {
        return WaterConsumerMeter::where('consumer_id',$consumerId)
        ->where('status',true);
    }
}
