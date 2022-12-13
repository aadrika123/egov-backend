<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

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

    //concession number generation
    public function concessionNo($id)
    {
        try {
            $count = PropActiveConcession::where('id', $id)
                ->select('id')
                ->get();
            $concessionNo = 'CON' . "/" . str_pad($count['0']->id, 5, '0', STR_PAD_LEFT);

            return $concessionNo;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
