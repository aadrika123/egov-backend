<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MPropRoadType extends Model
{
    use HasFactory;

    /**
     * | Get Road Type by road Width
     */
    public function getRoadTypeByRoadWidth($roadWidth)
    {
        $query = "SELECT * FROM m_prop_road_types
                WHERE range_from_sqft<=ROUND($roadWidth)
                ORDER BY range_from_sqft DESC LIMIT 1";
        return DB::select($query);
    }

    //written by prity pandey

    public function getById($req)
    {
        $list = MPropRoadType::select(
            'id',
            'prop_road_typ_id',
            'range_from_sqft',
            'range_upto_sqft',
            'effective_date',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    public function listMPropRoadType()
    {
        $list = MPropRoadType::select(
            'id',
            'prop_road_typ_id',
            'range_from_sqft',
            'range_upto_sqft',
            'effective_date',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }
}
