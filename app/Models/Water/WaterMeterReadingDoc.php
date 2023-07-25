<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterMeterReadingDoc extends Model
{
    use HasFactory;


    /**
     * | Save the doc for the demand generation process
     * | Save the meter reading document
     * | @param 
     */
    public function saveDemandDocs($meterDetails,$documentPath,$demandId)
    {
        $mWaterMeterReadingDoc = new WaterMeterReadingDoc();
        $mWaterMeterReadingDoc->demand_id       = $demandId;
        $mWaterMeterReadingDoc->meter_no        = $meterDetails['meterNo'];
        $mWaterMeterReadingDoc->file_name       = $documentPath['document'];
        $mWaterMeterReadingDoc->relative_path   = $documentPath['relaivePath'];
        $mWaterMeterReadingDoc->save();
    }
}
