<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropActiveObjectionFloor extends Model
{
    use HasFactory;

    /**
     * | 
     */
    public function  getfloorObjectionId($objId)
    {
        return PropActiveObjectionFloor::where('objection_id', $objId)
            // ->join('ref_prop_objection_types', 'ref_prop_objection_types.id', 'prop_active_objection_dtls.objection_type_id')
            // ->orderByDesc('objection_type_id')
            ->get();
    }
}
