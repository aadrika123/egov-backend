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

    /**
     * | Get occupancy type according to holding id
     */
    public function getOccupancyType($propertyId, $refTenanted)
    {
        $occupency = PropFloor::where('property_id', $propertyId)
            ->where('occupancy_type_mstr_id', $refTenanted)
            ->get();
        $check = collect($occupency)->first();
        if ($check) {
            $metaData = [
                'tenanted' => true
            ];
            return $metaData;
        }
        return  $metaData = [
            'tenanted' => false
        ];
        return $metaData;
    }

    /**
     * | Get usage type according to holding
     */
    public function getPropUsageCatagory($propertyId)
    {
        return PropFloor::select(
            'ref_prop_usage_types.usage_code'
        )
            ->join('ref_prop_usage_types', 'ref_prop_usage_types.id', '=', 'prop_floors.usage_type_mstr_id')
            ->where('property_id', $propertyId)
            ->orderByDesc('ref_prop_usage_types.id')
            ->get();
    }
}
