<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RefPropGbpropusagetype extends Model
{
    use HasFactory;

    /**
     * | Get GB prop usage types
       | Reference Function : masterSaf
     */
    public function getGbpropusagetypes()
    {
        return RefPropGbpropusagetype::on('pgsql::read')->select(
            'id',
            DB::raw('INITCAP(prop_usage_type) as prop_usage_type'),
        )
            ->get();
    }
}
