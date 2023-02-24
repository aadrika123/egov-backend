<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterConsumerDisconnection extends Model
{
    use HasFactory;

    /**
     * | Save the Consumer deactivated details 
     * | @param req
     */
    public function saveDeactivationDetails($request)
    {
        $mWaterDisconnection = new WaterConsumerDisconnection();
        // $mWaterDisconnection-> = $request[''];
    }
}
