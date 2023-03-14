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
    public function getCitizenSafs($citizenId)
    {
        return PropSaf::where('citizen_id', $citizenId)
            ->get();
    }
}
