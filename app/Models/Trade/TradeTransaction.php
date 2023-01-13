<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeTransaction extends Model
{
    use HasFactory;
    public $timestamps = false;

    public static function listByLicId($licenseId)
    {
        return self::select("*")
            ->where("temp_id", $licenseId)
            ->whereIn("status", [1, 2])
            ->first();
    }
}
