<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveTradeLicence extends Model
{
    use HasFactory;



    public function getLicenceNo($appId)
    {
        return ActiveTradeLicence::select('*')
            ->where('id', $appId)
            ->first();
    }
}
