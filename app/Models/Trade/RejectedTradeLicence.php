<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RejectedTradeLicence extends Model
{
    use HasFactory;

    public function owneres()
    {
        return $this->hasMany(RejectedTradeOwner::class,'temp_id',"id");
    }
}
