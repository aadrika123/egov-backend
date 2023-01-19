<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropFloor extends Model
{
    use HasFactory;

    /**
     * | Get Property Floors
     */
    public function getPropFloors($propertyId)
    {
        return DB::table('prop_floors')
            ->select('prop_floors.*', 'f.floor_name', 'u.usage_type', 'o.occupancy_type', 'c.construction_type')
            ->join('ref_prop_floors as f', 'f.id', '=', 'prop_floors.floor_mstr_id')
            ->join('ref_prop_usage_types as u', 'u.id', '=', 'prop_floors.usage_type_mstr_id')
            ->join('ref_prop_occupancy_types as o', 'o.id', '=', 'prop_floors.occupancy_type_mstr_id')
            ->join('ref_prop_construction_types as c', 'c.id', '=', 'prop_floors.const_type_mstr_id')
            ->where('property_id', $propertyId)
            ->get();
    }


    /**
     * | Used for Calculation Parameter
     * | Get Property Details
     */
    public function getFloorsByPropId($propertyId)
    {
        return DB::table('prop_floors')
            ->where('property_id', $propertyId)
            ->get();
    }
}
