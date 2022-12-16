<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropHarvestingLevelpending extends Model
{
    use HasFactory;

    public function getReceiverLevel($harvestingId, $senderRoleId)
    {
        return  PropHarvestingLevelpending::where('harvesting_id', $harvestingId)
            ->where('receiver_role_id', $senderRoleId)
            ->first();
    }


    public function getLevelsByConcessionId($id)
    {
        return PropHarvestingLevelpending::where('harvesting_id', $id)
            ->get();
    }
}
