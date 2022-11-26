<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class RefPropOccupancyType extends Model
{
    use HasFactory;

    public function propOccupancyType()
    {
        try {
            $constType = RefPropOccupancyType::select('id', 'occupancy_type as occupancyType')
                ->where('status', '1')
                ->get();
            return responseMsg(true, "Successfully Retrieved", $constType);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
