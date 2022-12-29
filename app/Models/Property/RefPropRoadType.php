<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;


class RefPropRoadType extends Model
{
    use HasFactory;

    public function propRoadType()
    {

        return RefPropRoadType::select('id', 'road_type')
            ->where('status', '1')
            ->get();
    }
}
