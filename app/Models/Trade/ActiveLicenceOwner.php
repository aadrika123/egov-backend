<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveLicenceOwner extends Model
{
    use HasFactory;

    public static function owneresByLId($licenseId)
    {
        $ownerDtl   = self::select("*")
                        ->where("licence_id",$licenseId)
                        ->where("status",1)
                        ->get();
        return $ownerDtl;
        
    }
}
