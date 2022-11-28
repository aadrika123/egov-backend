<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropConcessionLevelpending extends Model
{
    use HasFactory;

    public function getReceiverLevel($concessionId, $senderRoleId)
    {
        return  PropConcessionLevelPending::where('concession_id', $concessionId)
            ->where('receiver_role_id', $senderRoleId)
            ->first();
    }


    public function getLevelsByConcessionId($id)
    {
        return PropConcessionLevelPending::where('concession_id', $id)
            ->get();
    }
}
