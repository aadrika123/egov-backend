<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class RefPropUsageType extends Model
{
    use HasFactory;

    public function propUsageType()
    {
        try {
            $usageType = RefPropUsageType::select('id', 'usage_type as usageType')
                ->where('status', '1')
                ->get();
            return responseMsg(true, "Successfully Retrieved", $usageType);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
