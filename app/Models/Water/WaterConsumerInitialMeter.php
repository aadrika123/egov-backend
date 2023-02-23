<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterConsumerInitialMeter extends Model
{
    use HasFactory;

    /**
     * | Get the Meter Reading and the meter details by consumer no
     */
    public function getmeterReadingAndDetails($consumerId)
    {
        return WaterConsumerInitialMeter::where('water_consumer_initial_meters.status',1)
        ->where('water_consumer_initial_meters.consumer_id',$consumerId);
    }
}
