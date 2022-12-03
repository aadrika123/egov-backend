<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropActiveHarvesting extends Model
{
    use HasFactory;

    /**
     * |-------------------------- details of rainWaterHarvesting  -----------------------------------------------
     * | @param request
     */
    public function allRwhDetails($request)
    {
        $details = PropActiveHarvesting::select(
            'id',
            'name',
            'mobile_no AS mobileNo',
            'application_no AS applicationNo'
        )
            ->where('harvesting_status', false)
            ->where("application_no", $request->search)
            ->get();
        return responseMsg(true, "Dat According to Water Harvesting!", $details);
    }

    /**
     * |-------------------------- All details of rainWaterHarvesting according to id -----------------------------------------------
     * | @param request
     */
    public function allDetails($request)
    {
        $allRwhData = PropActiveHarvesting::select(
            'prop_active_harvestings.*'
        )
            ->where('id', $request->id)
            ->get();
        return $allRwhData;
    }
}
