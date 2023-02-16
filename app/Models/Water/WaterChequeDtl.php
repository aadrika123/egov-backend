<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterChequeDtl extends Model
{
    use HasFactory;

    public function chequeDtlById($request)
    {
        return WaterChequeDtl::select('*')
            ->where('id', $request->chequeId)
            ->where('status', 2)
            ->first();
    }
}
