<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentPropPenalty extends Model
{
    use HasFactory;

    /**
     * | Check Penalty By demand ID and Penalty Type
     */
    public function getPenaltyByDemandPenaltyID($demandId, $penaltyId)
    {
        return PaymentPropPenalty::where('saf_demand_id', $demandId)
            ->where('penalty_type_id', $penaltyId)
            ->first();
    }
}
