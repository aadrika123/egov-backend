<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropPenalty extends Model
{
    use HasFactory;

    /**
     * | Get Penalties by tran id
     */
    public function getPenalties($key, $id)
    {
        return PropPenalty::where('tran_id', $id)
            ->get();
    }
}
