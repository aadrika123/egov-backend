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
        return WaterConsumerInitialMeter::where('water_consumer_initial_meters.status', 1)
            ->where('water_consumer_initial_meters.consumer_id', $consumerId);
    }


    /**
     * | Save the consumer meter details when the monthely demand is generated
     * | @param request
     */
    public function saveConsumerReading($request, $meterDetails)
    {
        $mWaterConsumerInitialMeter = new WaterConsumerInitialMeter();
        $mWaterConsumerInitialMeter->consumer_id        = $request->consumerId;
        $mWaterConsumerInitialMeter->initial_reading    = $request->finalRading;
        $mWaterConsumerInitialMeter->emp_details_id     = authUser()->id;
        $mWaterConsumerInitialMeter->consumer_meter_id  = $meterDetails['meterId'];
        $mWaterConsumerInitialMeter->save();
    }
}
