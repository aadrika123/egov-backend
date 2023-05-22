<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropTax extends Model
{
    use HasFactory;

    /**
     * | Get Prop Taxes by PropID
     */
    public function getPropTaxesByPropId($propId)
    {
        return PropTax::where('prop_id', $propId)
            ->where('status', 1)
            ->get();
    }
}
