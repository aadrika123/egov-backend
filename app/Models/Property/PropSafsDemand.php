<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropSafsDemand extends Model
{
    use HasFactory;

    // Get Demand By SAF id
    public function getDemandBySafId($safId)
    {
        return PropSafsDemand::where('saf_id', $safId)
            ->get();
    }
}
