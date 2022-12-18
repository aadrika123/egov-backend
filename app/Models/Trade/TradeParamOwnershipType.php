<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeParamOwnershipType extends Model
{
    use HasFactory;
    public $timestamps=false;

    public Static function List()
    {
         return self::select("id","ownership_type")
                ->where("status",1)
                ->get();
    }
}
