<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPropBuildingRentalconst extends Model
{
    use HasFactory;
    //written by prity pandey

    /** 
     * | Get MPropBuildingRentalconst By ID
       | Reference Function : MPropBuildingRentalconstsById
     */
    public function getById($req)
    {
        $list = MPropBuildingRentalconst::select(
            'id',
            'x',
            'ulb_id',
            'effective_date',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    /**
     * | Get MPropBuildingRentalconst By ULB ID
       | Reference Function : allMPropBuildingRentalconstsList
     */
    public function listMPropBuildingRenConst()
    {
        $list = MPropBuildingRentalconst::select(
            'id',
            'x',
            'ulb_id',
            'effective_date',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }
}
