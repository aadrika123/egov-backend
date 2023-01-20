<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropPenaltyrebate extends Model
{
    use HasFactory;

    /**
     * | Get Rebate Or Penalty Amount by tranid
     */
    public function getPenalRebateByTranId($tranId, $headName)
    {
        return PropPenaltyrebate::where('tran_id', $tranId)
            ->where('head_name', $headName)
            ->orderByDesc('id')
            ->first();
    }
}
