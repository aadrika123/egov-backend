<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPropBuildingRentalrate extends Model
{
    use HasFactory;

    protected $hidden = ['created_at', 'updated_at'];

    public function show(array $req)
    {
        MPropBuildingRentalrate::view($req);
    }
}
