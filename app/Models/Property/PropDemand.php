<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PropDemand extends Model
{
    use HasFactory;

    /**
     * | Get the Last Demand Date by Property Id
     */
    public function readLastDemandDateByPropId($propId)
    {
        $propDemand = PropDemand::where('property_id', $propId)
            ->orderByDesc('id')
            ->first();
        return $propDemand;
    }

    /**
     * | Get Property Dues Demand by Property Id
     */
    public function getDueDemandByPropId($propId)
    {
        return PropDemand::select(
            'id',
            DB::raw("concat(qtr,'/',fyear) as quarterYear"),
            'arv',
            'qtr',
            'holding_tax',
            'water_tax',
            'education_cess',
            'health_cess',
            'latrine_tax',
            'additional_tax',
            'amount',
            'balance',
            'fyear',
            'adjust_amt',
            'due_date'
        )
            ->where('property_id', $propId)
            ->where('paid_status', 0)
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * | Get Property Demand by Property ID
     */
    public function getDemandByPropId($propId)
    {
        return PropDemand::where('prop_id', $propId)
            ->where('status', 1)
            ->get();
    }
}
