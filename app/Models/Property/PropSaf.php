<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropSaf extends Model
{
    use HasFactory;

    /**
     * | Get citizen safs
     */
    public function getCitizenSafs($citizenId, $ulbId)
    {
        return PropSaf::select('id', 'saf_no', 'citizen_id')
            ->where('citizen_id', $citizenId)
            ->where('ulb_id', $ulbId)
            ->get();
    }
}
