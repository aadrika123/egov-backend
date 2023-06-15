<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeOwner extends Model
{
    use HasFactory;
    public $timestamps = false;

    public function application()
    {
        return $this->belongsTo(TradeLicence::class,'temp_id',"id");
    }

    public function renewalApplication()
    {
        return $this->belongsTo(TradeRenewal::class,'temp_id',"id");
    }

    public static function owneresByLId($licenseId)
    {
        return self::select("*")
            ->where("temp_id", $licenseId)
            ->where("is_active", True)
            ->get();
    }

    public function getFirstOwner($licenseId)
    {
        return self::select('owner_name', 'mobile_no')
            ->where('temp_id', $licenseId)
            ->where('is_active', true)
            ->first();
    }
}
