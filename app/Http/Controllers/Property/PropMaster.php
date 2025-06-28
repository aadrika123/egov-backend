<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\RefPropUsageType;
use App\Models\Property\RefPropConstructionType;
use App\Models\Property\RefPropOccupancyType;
use App\Models\Property\RefPropType;
use App\Models\Property\RefPropRoadType;
use Illuminate\Http\Request;

class PropMaster extends Controller
{
    //usage type
    public function propUsageType()
    {
        $obj = new RefPropUsageType();
        return $obj->propUsageType();
    }

    //occupancy type
    public function propOccupancyType()
    {
        $obj = new RefPropOccupancyType();
        return $obj->propOccupancyType();
    }

    //road type
    public function propRoadType()
    {
        $obj = new RefPropRoadType();
        return $obj->propRoadType();
    }


    # ---------------------------------------------------------#
    # ----- APIs that are currently inactive or unused --------#
    # ---------------------------------------------------------#

    //constrction type
    public function propConstructionType()
    {
        $obj = new RefPropConstructionType();
        return $obj->propConstructionType();
    }

    //property type
    public function propPropertyType()
    {
        $obj = new RefPropType();
        return $obj->propPropertyType();
    }
}
