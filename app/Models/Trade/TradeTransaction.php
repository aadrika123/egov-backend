<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeTransaction extends Model
{
    use HasFactory;
    public $timestamps=false;


    public static function listByLicId($licenceId)
    {
        return self::select("*")
            ->where("related_id",$licenceId)
            ->whereIn('status', [1, 2])
            ->get();
    }
}
