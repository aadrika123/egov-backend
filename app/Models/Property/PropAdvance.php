<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropAdvance extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Store Function
     */
    public function store($req)
    {
        PropAdvance::create($req);
    }

    /**
     * | Get Property Advances and Adjust Amount
     */
    public function getPropAdvanceAdjustAmt($propId)
    {
        return DB::table("prop_advances as a")
            ->leftJoin("prop_adjustments as p", function ($join) {
                $join->on("p.tran_id", "=", "a.tran_id");
            })
            ->select(DB::raw("sum(coalesce(a.amount, 0)) as advance, sum(coalesce(p.amount, 0)) as adjustment_amt"))
            ->where("a.prop_id", "=", $propId)
            ->where("a.status", 1)
            ->groupBy("a.amount")
            ->first();
    }

    /**
     * | Get Cluster Advances and Adjust Amt
     */
    public function getClusterAdvanceAdjustAmt($clusterId)
    {
        return DB::table("prop_advances as a")
            ->leftJoin("prop_adjustments as p", function ($join) {
                $join->on("p.cluster_id", "=", "a.cluster_id");
            })
            ->select(DB::raw("sum(coalesce(a.amount, 0)) as advance, sum(coalesce(p.amount, 0)) as adjustment_amt"))
            ->where("a.cluster_id", "=", $clusterId)
            ->groupBy("a.amount")
            ->first();
    }
}
