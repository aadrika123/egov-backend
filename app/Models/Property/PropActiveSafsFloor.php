<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class PropActiveSafsFloor extends Model
{
    use HasFactory;

    /**
     * | Get Saf Floor Details by SAF id
     */
    public function getFloorsBySafId($safId)
    {
        return DB::table('prop_active_safs_floors')
            ->select('prop_active_safs_floors.*', 'f.floor_name', 'u.usage_type', 'o.occupancy_type', 'c.construction_type')
            ->join('ref_prop_floors as f', 'f.id', '=', 'prop_active_safs_floors.floor_mstr_id')
            ->join('ref_prop_usage_types as u', 'u.id', '=', 'prop_active_safs_floors.usage_type_mstr_id')
            ->join('ref_prop_occupancy_types as o', 'o.id', '=', 'prop_active_safs_floors.occupancy_type_mstr_id')
            ->join('ref_prop_construction_types as c', 'c.id', '=', 'prop_active_safs_floors.const_type_mstr_id')
            ->where('saf_id', $safId)
            ->get();
    }

    /**
     * | Get occupancy type according to Saf id
     */
    public function getOccupancyType($safId, $refTenanted)
    {
        $occupency = PropActiveSafsFloor::where('saf_id', $safId)
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
     * | Get usage type according to Saf NO
     */
    public function getSafUsageCatagory($safId)
    {
        return PropActiveSafsFloor::select(
            'ref_prop_usage_types.usage_code'
        )
            ->join('ref_prop_usage_types', 'ref_prop_usage_types.id', '=', 'prop_active_safs_floors.usage_type_mstr_id')
            ->where('saf_id', $safId)
            ->orderByDesc('ref_prop_usage_types.id')
            ->get();
    }
}
