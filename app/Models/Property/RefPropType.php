<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class RefPropType extends Model
{
    use HasFactory;

    public function propPropertyType()
    {
        try {
            $propType = RefPropType::select('id', 'property_type as propertyType')
                ->where('status', '1')
                ->get();
            return responseMsg(true, "Successfully Retrieved", $propType);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
