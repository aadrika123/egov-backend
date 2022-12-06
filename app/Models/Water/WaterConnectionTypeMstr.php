<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterConnectionTypeMstr extends Model
{
    use HasFactory;

    public function getConnectionType()
    {
        return  WaterConnectionTypeMstr::select('water_connection_type_mstrs.id', 'water_connection_type_mstrs.connection_type')
            ->where('status', 1)
            ->get();
    }
}
