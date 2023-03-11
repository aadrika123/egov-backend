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
            ->where('status', true)
            ->orderByDesc('id');
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
        $mWaterConsumerMeter = new WaterConsumerMeter();
        $mWaterConsumerMeter->consumer_id               = $req->consumerId;
        $mWaterConsumerMeter->connection_date           = $req->connectionDate;
        $mWaterConsumerMeter->emp_details_id            = authUser()->id;
        $mWaterConsumerMeter->connection_type           = $req->connectionType;
        $mWaterConsumerMeter->meter_no                  = $req->meterNo;
        $mWaterConsumerMeter->final_meter_reading       = $req->finalMeterReading;
        $mWaterConsumerMeter->meter_intallation_date    = $req->installationDate;
        $mWaterConsumerMeter->initial_reading           = $req->intialReading;
        $mWaterConsumerMeter->rate_per_month            = $req->ratePerMonth ?? 0;
        $mWaterConsumerMeter->save();
    }
}
