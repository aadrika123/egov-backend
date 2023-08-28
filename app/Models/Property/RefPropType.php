<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;
use Illuminate\Support\Facades\DB;

class RefPropType extends Model
{
    use HasFactory;

    /**
     * | Get All Property Types
     */
    public function propPropertyType()
    {
        return RefPropType::on('pgsql::read')->select(
            'id',
            DB::raw('INITCAP(property_type) as property_type')
        )
            ->where('status', 1)
            ->get();
    }
}
