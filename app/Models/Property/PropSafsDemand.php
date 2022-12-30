<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropSafsDemand extends Model
{
    use HasFactory;
    protected $guarded = [];

    // Get Demand By SAF id
    public function getDemandBySafId($safId)
    {
        return PropSafsDemand::where('saf_id', $safId)
            ->where('status', 1)
            ->get();
    }

    // Get Demand By ID
    public function getDemandById($id)
    {
        return PropSafsDemand::find($id);
    }

    // Get Existing Prop SAF Demand by financial quarter and safid
    public function getPropSafDemands($quarterYear, $qtr, $safId)
    {
        return PropSafsDemand::where('fyear', $quarterYear)
            ->where('qtr', $qtr)
            ->where('saf_id', $safId)
            ->first();
    }

    /**
     * | Save SAF Demand
     */
    public function saveSafDemands($req)
    {
        $metaReqs = [
            'waterTax' => $req->waterTax,
            'educationTax' => $req->educationTax,
            'healthCess' => $req->healthCess,
            'latrineTax' => $req->latrineTax,
            'additionTax' => $req->additionTax,
            'holdingTax' => $req->holdingTax,
            'totalTax' => $req->totalTax
        ];
        PropSafsDemand::create($metaReqs);
    }
}
