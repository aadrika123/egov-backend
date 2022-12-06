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
     * |-------------------------- Details of the Mutation accordind to ID -----------------------------------------------|
     * | @param request
     * | @var mutation
     */
    public function allMutation($request)
    {
        $mutation = PropActiveSaf::where('id', $request->id)
            ->where('property_assessment_id', 3)
            ->get();
        return $mutation;
    }


    /**
     * |-------------------------- Details of the ReAssisments according to ID  -----------------------------------------------|
     * | @param request
     * | @var reAssisment
     */
    public function allReAssisment($request)
    {
        $reAssisment = PropActiveSaf::where('id', $request->id)
            ->where('property_assessment_id', 2)
            ->get();
        return $reAssisment;
    }


    /**
     * |-------------------------- Details of the NewAssisment according to ID  -----------------------------------------------|
     * | @var safDetails
     */
    public function allNewAssisment($request)
    {
        $newAssisment = PropActiveSaf::where('id', $request->id)
            ->where('property_assessment_id', 1)
            ->get();
        return $newAssisment;
    }


    /**
     * |-------------------------- safId According to saf no -----------------------------------------------|
     */
    public function getSafId($safNo)
    {
        return PropActiveSaf::where('saf_no',$safNo)
        ->select('id')
        ->get()
        ->first();
    }
}
