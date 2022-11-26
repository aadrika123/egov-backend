<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class RefPropConstructionType extends Model
{
    use HasFactory;

    public function propConstructionType()
    {
        try {
            $constType = RefPropConstructionType::select('id', 'construction_type as constructionType')
                ->where('status', '1')
                ->get();
            return responseMsg(true, "Successfully Retrieved", $constType);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
