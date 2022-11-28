<?php

namespace App\Models\Property;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropActiveSaf extends Model
{
    use HasFactory;

    /**
     * |-------------------------- safs list whose Holding are not craeted -----------------------------------------------|
     * | @var safDetails
     */
    public function allNonHoldingSaf()
    {
        try {
            $allSafList = PropActiveSaf::select(
                'id AS SafId'
            )
                ->get();
            return responseMsg(true, "Saf List!", $allSafList);
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }


     /**
     * |-------------------------- safs list whose Holding are not craeted -----------------------------------------------|
     * | @var safDetails
     */
    public function allMutation($request)
    {
        $mutation=PropActiveSaf::where('id',$request->id)
        ->get();
        return responseMsg(true, "Dat According to Mutation!", $mutation);
    }
}
