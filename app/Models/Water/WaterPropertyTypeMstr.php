<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterPropertyTypeMstr extends Model
{
    use HasFactory;


    // get all property type details
    public function getAllPropertyType()
    {
        return  WaterPropertyTypeMstr::select('water_property_type_mstrs.id', 'water_property_type_mstrs.property_type')
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();
    }
}
