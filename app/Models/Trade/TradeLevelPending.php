<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeLevelPending extends Model
{
    use HasFactory;
    public $timestamps=false;


    public static function getLevelData($licenceId)
    {
        return  TradeLevelPending::select("*")
                    ->where("licence_id",$licenceId)
                    ->where("status",1)
                    ->where("verification_status",0)
                    ->orderBy("id","DESC")
                    ->first();
            
    }
}
