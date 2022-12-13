<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

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
        return $objection;
    }

    //objection number generation
    public function objectionNo($id)
    {
        try {
            $count = PropActiveObjection::where('id', $id)
                ->select('id')
                ->get();
            $_objectionNo = 'OBJ' . "/" . str_pad($count['0']->id, 5, '0', STR_PAD_LEFT);

            return $_objectionNo;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
