<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UlbMaster extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function districtWiseUlb($req)
    {
        return UlbMaster::select('*')
            ->where('district_code', $req->districtCode)
            ->get();
    }
}
