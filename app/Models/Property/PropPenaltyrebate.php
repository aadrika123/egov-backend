<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropPenaltyrebate extends Model
{
    use HasFactory;
    protected $guarded = [];

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

    /**
     * | Get Penalty Rebates
     */
    public function getPropPenalRebateByTranId($tranId)
    {
        return PropPenaltyrebate::where('tran_id', $tranId)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * | Post Rebate Penalties
     */
    public function postRebatePenalty($reqs)
    {
        PropPenaltyrebate::create($reqs);
    }
}
