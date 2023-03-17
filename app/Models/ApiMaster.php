<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiMaster extends Model
{
    use HasFactory;

    /**
     * | get Api with Advertisment 
     * | ADV = 76
     */
    public function getAdvApi()
    {
        $adv = 76;
        return ApiMaster::select(
            'end_point'
        )
            ->where('id', $adv)
            ->first();
    }
}
