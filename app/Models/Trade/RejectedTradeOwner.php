<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RejectedTradeOwner extends Model
{
    use HasFactory;
    public $timestamps=false;

    public function application()
    {
        return $this->belongsTo(RejectedTradeLicence::class,'temp_id',"id");
    }
}
