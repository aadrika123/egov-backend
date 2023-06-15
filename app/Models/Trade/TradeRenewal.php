<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeRenewal extends Model
{
    use HasFactory;
    public $timestamps=false;

    public function owneres()
    {
        return $this->hasMany(TradeOwner::class,'temp_id',"id");
    }
}
