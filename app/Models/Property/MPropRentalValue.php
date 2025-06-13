<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPropRentalValue extends Model
{
    use HasFactory;
    //written by prity pandey

    /**
     * | Get Rental Value by usage type
       | Reference Function : MPropRentalValueById
     */
    public function getById($req)
    {
        $list = MPropRentalValue::select(
            'id',
            'usage_types_id',
            'zone_id',
            'construction_types_id',
            'rate',
            'ulb_id',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    /**
     * | Get Rental Value by usage type
       | Reference Function : allMPropRentalValueList
     */
    public function listMPropRentalValue()
    {
        $list = MPropRentalValue::select(
            'id',
            'usage_types_id',
            'zone_id',
            'construction_types_id',
            'rate',
            'ulb_id',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }
}
