<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeChequeDtl extends Model
{
    use HasFactory;


    public function chequeDtlById($request)
    {
        return TradeChequeDtl::select('*')
            ->where('id', $request->chequeId)
            ->where('state', 2)
            ->first();
    }
}
