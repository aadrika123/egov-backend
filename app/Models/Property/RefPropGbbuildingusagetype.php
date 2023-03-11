<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropGbbuildingusagetype extends Model
{
    use HasFactory;

    /**
     * | Get GB building usage types
     */
    public function getGbbuildingusagetypes()
    {
        return RefPropGbbuildingusagetype::select('*')
            ->get();
    }
}
