<?php

namespace App\Repository\Property\Concrete;

use App\Models\Property\ActiveSafsOwnerDtl;
use App\Models\Property\PropOwnerDtl;
use App\Repository\Property\Interfaces\iConcessionRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;



class ConcessionRepository implements iConcessionRepository
{
    //updation of concession detail
    public function UpdateConDetail(Request $request)
    {
        try {
            $device = PropOwnerDtl::find($request->propDtlId);
            $device->gender = $request->gender;
            $device->dob = $request->dob;
            $device->is_armed_force = $request->isArmedForce;
            $device->is_specially_abled = $request->isSpeciallyAbled;
            $device->updated_at = Carbon::now();
            $device->save();
            return responseMsg(true, "Successfully Updated", "");
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    //document upload
    public function UpdateDocuments(Request $request)
    {
        $uploaded_files = $request->file->store('public/uploads/');
        return ["result" => $uploaded_files];
    }
}
