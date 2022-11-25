<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropLevelPending extends Model
{
    use HasFactory;

    // Get SAF level Pending By safid and current role ID
    public function getLevelBySafReceiver($safId, $currentRoleId)
    {
        return PropLevelPending::where('saf_id', $safId)
            ->where('receiver_role_id', $currentRoleId)
            ->orderByDesc('id')
            ->limit(1)
            ->first();
    }
}
