<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropSafTax extends Model
{
    use HasFactory;

    /**
     * | Read Saf Taxes by SafId
     */
    public function getSafTaxesBySafId($safId)
    {
        return PropSafTax::where('saf_id', $safId)
            ->where('status', 1)
            ->get();
    }
}
