<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeParamFirmType extends Model
{
    use HasFactory;
    public $timestamps=false;

    public Static function List()
    {
        return self::select("id","firm_type")
                ->where("status",1)
                ->get();
    }
}
