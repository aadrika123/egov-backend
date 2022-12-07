<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterOwnerTypeMstr extends Model
{
    use HasFactory;

     // water owners detais
     public function getallOwnwers()
     {
         return WaterOwnerTypeMstr::select('water_owner_type_mstrs.id', 'water_owner_type_mstrs.owner_type')
             ->where('status', 1)
             ->get();
     }
}
