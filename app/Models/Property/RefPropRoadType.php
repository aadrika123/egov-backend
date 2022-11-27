<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;


class RefPropRoadType extends Model
{
    use HasFactory;

    public function propRoadType()
    {
        try {
            $constType = RefPropRoadType::select('id', 'road_type as roadType')
                ->where('status', '1')
                ->get();
            return responseMsg(true, "Successfully Retrieved", $constType);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
