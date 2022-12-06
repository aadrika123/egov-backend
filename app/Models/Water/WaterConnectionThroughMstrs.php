<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterConnectionThroughMstrs extends Model
{
    use HasFactory;

    public function getAllThrough()
    {
        return  WaterConnectionThroughMstrs::select('water_connection_through_mstrs.id', 'water_connection_through_mstrs.connection_through')
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();
    }
}
