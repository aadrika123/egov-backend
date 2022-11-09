<?php

namespace App\Repository\Grievance\Concrete;

use App\Repository\Grievance\Interfaces\iGrievance;
use App\Models\Grievance\GrievanceApplication;    //<---------- here model
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NewGrievanceRepository implements iGrievance
{
    
    /**
     * | code : Sam Kerketta
     * | ----------------- Get Connection Through / Water ------------------------------- |
     * | @var connectionThrough 
     * | #request null
     * | Operation : saving data
     */

    public function postFileComplain(Request $request)
    {
        $validateUser = Validator::make(
            $request->all(),
            [
                'wardId'                 => 'required',
                'complaintType'          => 'required',
                'complaintSubType'       => 'required',
                'complaintPincode'       => 'required',
                'complaintCity'          => 'required',
                'complaintLocality'      => 'required',
                'complaintWardNo'        => 'required',
                'complaintLandmark'      => 'required',
                'complaintHouseNo'       => 'required',
                'complaintDescription'   => 'required',
            ]
        );

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'message' => $validateUser->errors()
            ], 401);
        }

        DB::beginTransaction();
        try {
            // $newApplication = new GrievanceApplication();   //<--------------- here model
            // $newApplication->complain_type_id = $request->complainTypeId;
            // $newApplication->complain_sub_type_id = $request->complainSubTypeId;
            // $newApplication->complain_pincode = $request->complainPincode;
            // $newApplication->complain_city = $request->complainCity;
            // $newApplication->complain_locality = $request->complainLocality;
            // $newApplication->complain_ward_no = $request->complainWardNo;
            // $newApplication->complain_house_no = $request->complainHouseNo;
            // $newApplication->complain_landmark = $request->complainLandmark;
            // $newApplication->complain_image = $request->complainImage;
            // $newApplication->complaint_description = $request->complaintDescription;

            // // Generating Application No 
            $now = Carbon::now();
            // $applicationNo = 'APP/grievance' . $now->getTimeStamp();    //<-------------- here
            $complainNo = 'G' . $request->wardId . $now->getTimeStamp(); //<-------------- here
            // $newApplication->complain_no = $complainNo;
            // $newApplication->application_no = $applicationNo;
            // $newApplication->ulb_id = auth()->user()->ulb_id;
            // $newApplication->citizen_id = auth()->user()->id;
            // $newApplication->user_id = auth()->user()->id;
            // $newApplication->save();

            DB::commit();

            $data = ["complaintNo" => $complainNo, "ComplaintDate" => $now->getTimeStamp(), "pendingStatus" => 1];
            return responseMsg(true, "Successfully Saved !", [$data]);
        } catch (Exception $e) {
            DB::rollBack();
            return ($e);
        }
    }

    /**
     * | code : Sam Kerketta
     * | ----------------- Get Connection Through / Water ------------------------------- |
     * | @var connectionThrough 
     * | #request null
     * | Operation : get all coplainlis by id
     */

    public function getAllComplainById($id)
    {
        $data =  DB::table('grievance_application AS t') //<----------- here
            ->select(
                't.complaint_date AS complain',
                't.complaint_status AS complainStatus',
                't.complaint_no AS complainNo',
                't.complaint_description AS complainDescription',
                't.complaint_type AS complainType',
                't.complaint_sub_type AS complainSubType',
                't.complaint_pincode AS compalinPincode',
                't.complaint_city AS complainCity',
                't.complaint_locality AS complainLocality',
                't.complaint_ward_no AS wardNo',
                't.complaint_house_no AS houseNo',
                't.complaint_landmark AS Landmark'
            )->where('id', $id)
            ->get();
        return responseMsg(true, "data fetched !", $data);
    }
}
