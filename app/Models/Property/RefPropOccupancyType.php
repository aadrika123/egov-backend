<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RefPropOccupancyType extends Model
{
    use HasFactory;

    /** 
     * | Get Property Occupancy Types
       | Common Function
     */
    public function propOccupancyType()
    {
        return RefPropOccupancyType::on('pgsql::read')->select(
            'id',
            DB::raw('INITCAP(occupancy_type) as occupancy_type')
        )
            ->where('status', 1)
            ->get();
    }
}
