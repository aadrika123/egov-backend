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
            'guardian_name AS guardianName',
            'mobile_no AS mobileNo',
            'building_address AS buildingAddress',
            'workflow_id AS workflowId',
            'ward_id AS wardId'
        )
            ->where('harvesting_status', false)
            ->get();
        return $details;
    }
}
