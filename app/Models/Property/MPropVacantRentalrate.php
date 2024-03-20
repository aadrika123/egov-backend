<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPropVacantRentalrate extends Model
{
    use HasFactory;
    //written by prity pandey

    public function getById($req)
    {
        $list = MPropVacantRentalrate::select(
            'id',
            'prop_road_type_id',
            'rate',
            'ulb_type_id',
            'effective_date',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    public function listMPropVacantRetlRate()
    {
        $list = MPropVacantRentalrate::select(
            'id',
            'prop_road_type_id',
            'rate',
            'ulb_type_id',
            'effective_date',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }
}
