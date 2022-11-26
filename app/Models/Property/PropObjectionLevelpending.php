<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropObjectionLevelpending extends Model
{
    use HasFactory;

    // Get Current Objection Id of the Receiver
    public function getCurrentObjByReceiver($objectionId, $senderRoleId)
    {
        return PropObjectionLevelpending::where('objection_id', $objectionId)
            ->where('receiver_role_id', $senderRoleId)
            ->first();
    }
}
