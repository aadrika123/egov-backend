<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeParamCategoryType extends Model
{
    use HasFactory;
    public $timestamps=false;

    public static function List()
    {
        return self::select("id","category_type")
                ->where("status",1)
                ->get();
                
    }
}
