<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropActiveConcession extends Model
{
    use HasFactory;


    /**
     * |-------------------------- details of all concession according id -----------------------------------------------
     * | @param request
     */
    public function allConcession($request)
    {
        $concession = PropActiveConcession::where('id', $request->id)
            ->get();
        return $concession;
    }
}
