<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeParamOwnershipType extends Model
{
    use HasFactory;
    public $timestamps=false;

    public function activeApplication()
    {
        return $this->hasMany(ActiveTradeLicence::class,'ownership_type_id',"id")->where("is_active",true);
    }
    public function rejectedApplication()
    {
        return $this->hasMany(RejectedTradeLicence::class,'ownership_type_id',"id")->where("is_active",true);
    }
    public function approvedApplication()
    {
        return $this->hasMany(TradeLicence::class,'ownership_type_id',"id");
    }
    public function renewalApplication()
    {
        return $this->hasMany(TradeRenewal::class,'ownership_type_id',"id");
    }
    public Static function List()
    {
         return self::select("id","ownership_type")
                ->where("status",1)
                ->get();
    }
}
