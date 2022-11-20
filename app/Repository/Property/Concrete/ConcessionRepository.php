<?php

namespace App\Repository\Property\Concrete;

use App\Models\Property\ActiveSafsOwnerDtl;
use App\Models\Property\PropOwnerDtl;
use App\Repository\Property\Interfaces\iConcessionRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Models\Property\PropConcession;
use App\Models\Property\PropProperty;

class ConcessionRepository implements iConcessionRepository
{
    //updation of concession detail
    public function UpdateConDetail(Request $request)
    {
        try {
            $device = new PropConcession;
            $device->property_id = $request->propertyId;
            $device->saf_id = $request->safId;
            $device->application_no = $request->applicationNo;
            $device->applicant_name = $request->applicantName;
            $device->gender = $request->gender;
            $device->dob = $request->dob;
            $device->is_armed_force = $request->armedForce;
            $device->is_specially_abled = $request->speciallyAbled;
            $device->remarks = $request->remarks;
            $device->user_id = $request->userId;
            $device->doc_type = $request->docType;
            $device->status = $request->status;
            $device->created_at = Carbon::now();
            $device->date = Carbon::now();
            $device->save();
            return responseMsg(true, "Successfully Updated", "");
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    //document upload
    public function UpdateDocuments(Request $request, $id)
    {
        $user_id = auth()->user()->id;

        try {

            $device = new PropConcession;
            $device->property_id = $request->propertyId;
            $device->saf_id = $request->safId;
            $device->application_no = $request->applicationNo;
            $device->applicant_name = $request->applicantName;
            $device->gender = $request->gender;
            $device->dob = $request->dob;
            $device->is_armed_force = $request->armedForce;
            $device->is_specially_abled = $request->speciallyAbled;
            $device->remarks = $request->remarks;
            $device->user_id = $user_id;
            $device->doc_type = $request->docType;
            $device->status = $request->status;
            $device->created_at = Carbon::now();
            $device->date = Carbon::now();
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

            return responseMsg('200', 'Successfully Uploaded', $device);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    ////get owner details
    public function postHolding(Request $request)
    {
        try {

            $request->validate([
                'holdingNo' => 'required'
            ]);
            $user = PropProperty::where('holding_no', $request->holdingNo)
                ->get();
            if (!empty($user['0'])) {
                return responseMsg(true, 'True', $user);
            }
            return responseMsg(false, "False", "");
            // return $user['0'];
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
