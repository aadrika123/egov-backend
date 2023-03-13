<?php

namespace App\Models\Water;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

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
    public function saveMeterDetails($req, $documentPath)
    {
        $meterStatus = null;
        $refConnectionType = Config::get('waterConstaint.WATER_MASTER_DATA.METER_CONNECTION_TYPE');
        if ($req->connectionType = $refConnectionType['Meter/Fixed']) {
            $req->connectionType = 1;
            $meterStatus = 0;
        }
        if ($req->connectionType = $refConnectionType['Meter']) {
            $installationDate = Carbon::now();
        }
        $mWaterConsumerMeter = new WaterConsumerMeter();
        $mWaterConsumerMeter->consumer_id               = $req->consumerId;
        $mWaterConsumerMeter->connection_date           = $req->connectionDate;
        $mWaterConsumerMeter->emp_details_id            = authUser()->id;
        $mWaterConsumerMeter->connection_type           = $req->connectionType;
        $mWaterConsumerMeter->meter_no                  = $req->meterNo ?? null;
        $mWaterConsumerMeter->meter_intallation_date    = $installationDate ?? null;
        $mWaterConsumerMeter->initial_reading           = $req->newMeterInitialReading ?? null;
        // $mWaterConsumerMeter->rate_per_month            = $req->ratePerMonth ?? 0;
        $mWaterConsumerMeter->meter_status              = $meterStatus ?? 1;
        $mWaterConsumerMeter->save();
    }
}
