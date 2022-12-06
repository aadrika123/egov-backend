<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropProperty extends Model
{
    use HasFactory;

    // Get Property Of the Citizen
    public function getUserProperties($userId)
    {
        return PropProperty::where('user_id', $userId)
            ->get();
    }

    // get Prpoperty id
    public function getPropertyId($holdingNo)
    {
        return PropProperty::where('holding_no',$holdingNo)
        ->select('id')
        ->get()
        ->first();
    }
}
