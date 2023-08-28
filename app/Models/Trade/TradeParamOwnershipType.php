<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeParamOwnershipType extends TradeParamModel
{
    use HasFactory;
    public $timestamps=false;
    protected $connection;
    public function __construct($DB=null)
    {
        parent::__construct($DB);
    }

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
    public function List()
    {
         return self::select("id","ownership_type")
                ->where("status",1)
                ->get();
    }
}
