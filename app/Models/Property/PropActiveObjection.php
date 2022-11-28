<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropActiveObjection extends Model
{
    use HasFactory;


    /**
     * |-------------------------- details of all concession according id -----------------------------------------------
     * | @param request
     */
    public function allObjection($request)
    {
        $objection = PropActiveObjection::where('id', $request->id)
            ->get();
        return responseMsg(true, "Dat According to all objection!", $objection);
    }
}
