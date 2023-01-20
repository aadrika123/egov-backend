<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
