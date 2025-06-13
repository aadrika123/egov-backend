<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZoneMaster extends Model
{
    use HasFactory;

    /**
     * | The table associated with the model.
       | Common Function
     */
    public function getZone($ulbId)
    {
        return ZoneMaster::on('pgsql::read')->select('id', 'zone')
            ->where('ulb_id', $ulbId)
            ->get();
    }
}
