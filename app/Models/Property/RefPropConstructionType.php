<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;
use Illuminate\Support\Facades\DB;

class RefPropConstructionType extends Model
{
    use HasFactory;

    /** 
     * | get property construction type where status is 1
       | Common Function
    */
    public function propConstructionType()
    {
        return RefPropConstructionType::on('pgsql::read')->select(
            'id',
            DB::raw('INITCAP(construction_type) as construction_type')
        )
            ->where('status', 1)
            ->get();
    }
}
