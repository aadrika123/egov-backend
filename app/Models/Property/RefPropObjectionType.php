<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class RefPropObjectionType extends Model
{
    use HasFactory;

    /** 
     * | objection type master data
       | Reference Function : objectionType()
    */
    public function objectionType()
    {
        $objectionType = RefPropObjectionType::on('pgsql::read')->where('status', 1)
            ->select('id', 'type')
            ->get();
        return $objectionType;
    }
}
