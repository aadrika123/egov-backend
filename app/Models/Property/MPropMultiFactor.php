<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPropMultiFactor extends Model
{
    use HasFactory;

    /**
     * | Get Multi Factors by usage type
     */
    public function getMultiFactorsByUsageType($usageTypeId)
    {
        return MPropMultiFactor::where('usage_type_id', $usageTypeId)
            ->where('status', 1)
            ->get();
    }

    /**
     * | Get All Multi Factors
     */
    public function multiFactorsLists()
    {
        return MPropMultiFactor::where('status', 1)
            ->get();
    }

    //written by prity pandey

    public function getById($req)
    {
        $list = MPropMultiFactor::select(
            'id',
            'usage_type_id',
            'multi_factor',
            'effective_date',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }


    public function listMPropMultiFactor()
    {
        $list = MPropMultiFactor::select(
            'id',
            'usage_type_id',
            'multi_factor',
            'effective_date',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }
}
