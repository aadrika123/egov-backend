<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RefPropOwnershipType extends Model
{
    use HasFactory;

    /**
     * | Get Property Ownership Types
       | Reference Function : masterSaf
     */
    public function getPropOwnerTypes()
    {
        return RefPropOwnershipType::on('pgsql::read')->select(
            'id',
            DB::raw('INITCAP(ownership_type) as ownership_type')
        )
            ->where('status', 1)
            ->get();
    }
}
