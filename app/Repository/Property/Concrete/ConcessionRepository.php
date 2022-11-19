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
            $device->is_armed_force = $request->armedForce;
            $device->is_specially_abled = $request->speciallyAbled;
            $device->updated_at = Carbon::now();
            $device->save();
            return responseMsg(true, "Successfully Updated", "");
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    //document upload
    public function UpdateDocuments(Request $request, $id)
    {

        try {

            $device = PropOwnerDtl::find($id);
            $device->gender = $request->gender;
            $device->dob = $request->dob;
            $device->is_armed_force = $request->armedForce;
            $device->is_specially_abled = $request->speciallyAbled;
            $device->created_at = Carbon::now();
            $device->updated_at = Carbon::now();
            $device->save();

            //gender Doc
            if ($file = $request->file('genderDoc')) {

                $name = time() . $file . '.' . $file->getClientOriginalExtension();
                $path = public_path('concession/genderDoc');
                $file->move($path, $name);
            }

            // dob Doc
            if ($file = $request->file('dobDoc')) {

                $name = time() . $file . '.' . $file->getClientOriginalExtension();
                $path = public_path('concession/dobDoc');
                $file->move($path, $name);
            }

            // specially abled Doc
            if ($file = $request->file('speciallyAbledDoc')) {

                $name = time() . $file . '.' . $file->getClientOriginalExtension();
                $path = public_path('concession/speciallyAbledDoc');
                $file->move($path, $name);
            }

            // Armed force Doc
            if ($file = $request->file('armedForceDoc')) {

                $name = time() . $file . '.' . $file->getClientOriginalExtension();
                $path = public_path('concession/armedForceDoc');
                $file->move($path, $name);
            }

            return responseMsg('200', 'Successfully Uploaded', $name);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
