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
}
