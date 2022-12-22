<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropOccupancyType extends Model
{
    use HasFactory;

    public function propOccupancyType()
    {
        return RefPropOccupancyType::select('id', 'occupancy_type')
            ->where('status', 1)
            ->get();
    }
}
