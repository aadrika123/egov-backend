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
        return WaterConsumerMeter::where('consumer_id', $consumerId)
            ->where('status', true);
    }

    /**
     * | Update the final Meter reading while Generation of Demand
     * | @param
     */
    public function saveMeterReading($req)
    {
        $mWaterConsumerMeter = WaterConsumerMeter::where('consumer_id', $req->consumerId)
            ->where('status', true)
            ->orderByDesc('id')
            ->first();

        $mWaterConsumerMeter->final_meter_reading = $req->finalRading;
        $mWaterConsumerMeter->save();
        return $mWaterConsumerMeter->id;
    }

    /**
     * | Save Meter Details While intallation of the new meter 
     * | @param 
     */
    public function saveMeterDetails($req)
    {
        // $mWaterConsumerMeter = new WaterConsumerMeter();
        // $mWaterConsumerMeter->consumer_id               = $req-> ;
        // $mWaterConsumerMeter->connection_date           = $req-> ;
        // $mWaterConsumerMeter->emp_details_id            = $req-> ;
        // $mWaterConsumerMeter->connection_type           = $req-> ;
        // $mWaterConsumerMeter->meter_no                  = $req-> ;
        // $mWaterConsumerMeter->final_meter_reading       = $req-> ;
        // $mWaterConsumerMeter->meter_intallation_date    = $req-> ;
        // $mWaterConsumerMeter->initial_reading           = $req-> ;
        // $mWaterConsumerMeter->rate_per_month            = $req-> ;
        // $mWaterConsumerMeter->save();

    }
}
