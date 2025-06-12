<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;
use Illuminate\Support\Facades\DB;

class RefPropRoadType extends Model
{
    use HasFactory;

    /**
     * Get Property Road Types
       | Common Function
     */
    public function propRoadType()
    {

        return RefPropRoadType::on('pgsql::read')->select(
            'id',
            DB::raw('INITCAP(road_type) as road_type')
        )
            ->where('status', '1')
            ->get();
    }
}
