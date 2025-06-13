<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropActiveObjectionOwner extends Model
{
    use HasFactory;

    
    /** 
     * |  Get Owner Detail by Objection ID
       | Common Function
    */
    public function getOwnerDetail($objId)
    {
        return PropActiveObjectionOwner::select('*')
            ->where('objection_id', $objId)
            ->get();
    }

    /**
     * |  Get Owner Edit Detail by Objection ID
       | Reference Function : approvalRejection
    */
    public function getOwnerEditDetail($objId)
    {
        return PropActiveObjectionOwner::select('*')
            ->where('objection_id', $objId)
            ->first();
    }
}
