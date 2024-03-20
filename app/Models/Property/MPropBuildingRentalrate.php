<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPropBuildingRentalrate extends Model
{
    use HasFactory;

    /**
     * | Get Rental Rate by 
     */
    public function getRentalRates()
    {
        return MPropBuildingRentalrate::select('id', 'prop_road_type_id', 'construction_types_id', 'rate', 'effective_date', 'status')
            ->where('status', 1)
            ->get();
    }

    //written by prity pandey

    public function getById($req)
    {
        $list = MPropBuildingRentalrate::select(
            'id',
            'prop_road_type_id',
            'construction_types_id',
            'rate',
            'effective_date',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }


    public function listMPropBuildingRentRate()
    {
        $list = MPropBuildingRentalrate::select(
            'id',
            'prop_road_type_id',
            'construction_types_id',
            'rate',
            'effective_date',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }
}
