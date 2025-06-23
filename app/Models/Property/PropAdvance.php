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
       | Common Function
     */
    public function store($req)
    {
        PropAdvance::create($req);
    }

    /**
     * | Get Property Advances and Adjust Amount
       | Common Function
     */
    public function getPropAdvanceAdjustAmt($propId)
    {
        return DB::table("prop_advances as a")
            ->leftJoin("prop_adjustments as p", function ($join) {
                $join->on("p.tran_id", "=", "a.tran_id");
            })
            ->select(DB::raw("sum(coalesce(a.amount, 0)) as advance, sum(coalesce(p.amount, 0)) as adjustment_amt"))
            ->where("a.property_id", "=", $propId)
            ->where("a.status", 1)
            ->groupBy("a.amount")
            ->first();
    }
    /**
     * | Get Property Advances and Adjust Amount
     */
    public function getPropAdvanceAdjustAmtv1($propId)
    {
        return DB::table("prop_advances as a")
            ->leftJoin("prop_adjustments as p", function ($join) {
                $join->on("p.tran_id", "=", "a.tran_id");
            })
            ->select(DB::raw("sum(coalesce(a.amount, 0)) as advance, sum(coalesce(p.amount, 0)) as adjustment_amt"))
            ->whereIn("a.property_id", $propId)
            ->where("a.status", 1)
            ->groupBy("a.amount")
            ->get();
    }

    /**
     * | Get Cluster Advances and Adjust Amt
       | Reference Function : getClusterHoldingDues
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

    /**
     * | Get Property Advance by ID
       | Reference Function : deactivate()
     */
    public function deactivateAdvanceByTrId($tranId)
    {
        return self::where("tran_id", $tranId)->update([
            "status" => 0
        ]);
    }
}
