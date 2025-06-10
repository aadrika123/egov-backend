<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropActiveObjectionOwner extends Model
{
    use HasFactory;

    
    /* 
    * |  Get Owner Detail by Objection ID
    */
    public function getOwnerDetail($objId)
    {
        return PropActiveObjectionOwner::select('*')
            ->where('objection_id', $objId)
            ->get();
    }

    /* 
    * |  Get Owner Edit Detail by Objection ID
    */
    public function getOwnerEditDetail($objId)
    {
        return PropActiveObjectionOwner::select('*')
            ->where('objection_id', $objId)
            ->first();
    }
}
