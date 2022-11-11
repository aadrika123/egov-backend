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
     * | ----------------- Saving application data ------------------------------- |
     * | @param request
     * | @var validateUser 
     * | @var complainNo
     * | @var now
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
                // 'complaintWardNo'        => 'required',
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
            // $newApplication->complaint_comment = $request->complaintComment;

            // // Generating Application No 
            $now = Carbon::now();
            // $applicationNo = 'APP/grievance' . $now->getTimeStamp();    //<-------------- here
            $complainNo = 'PG-PGR' . date("-Y") . date("-d-m") . sprintf("%06d", mt_rand(1, 999999)); //<-------------- here
            // $newApplication->complain_no = $complainNo;
            // $newApplication->application_no = $applicationNo;
            // $newApplication->ulb_id = auth()->user()->ulb_id;
            // $newApplication->citizen_id = auth()->user()->id;
            // $newApplication->user_id = auth()->user()->id;
            // $newApplication->save();

            DB::commit();

            $data = ["complaintNo" => $complainNo, "ComplaintDate" => date("d/m/y"), "pendingStatus" => 1];
            return responseMsg(true, "Successfully Saved !", [$data]);
        } catch (Exception $e) {
            DB::rollBack();
            return ($e);
        }
    }

    /**
     * | code : Sam Kerketta
     * | ----------------- Get All Complain ById  ------------------------------- |
     * | @var connectionThrough 
     * | #request null
     * | Operation : get all coplainlis by id
     */

    public function getAllComplainById($id)
    {
        $readApplicationData =  DB::table('grievance_application AS t') //<----------- here
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
        return responseMsg(true, "data fetched !", $readApplicationData);
    }

    // /**
    //  * | code : Sam Kerketta
    //  * | ----------------- Get Connection Through  ------------------------------- |
    //  * | @param req
    //  * | @param id
    //  * | @var readApplicationDetail 
    //  * | Operation : adding rating to the application
    //  */
    // public function updateRateComplaintById(Request $req, $id)
    // {
    //     try {
    //         $readApplicationDetail = DB::table('grievance_application AS t') //<---- here
    //             ->find($id)
    //             ->update([
    //                 't.complaint_rate' => $req->complaintRate,
    //                 't.complaint_remark' => $req->complaint_remark,
    //                 't.complaint_comment' => $req->complaintComment
    //             ]);
    //         if ($readApplicationDetail) {
    //             return responseMsg(true, "rated Success!", "");
    //         }
    //     } catch (Exception $e) {
    //         return $e;
    //     }
    // }

    // /**
    //  * | code : Sam Kerketta
    //  * | ----------------- Reopen Complain ById  ------------------------------- |
    //  * | @var readApplicationDetailList 
    //  * | @param req
    //  * | @param id
    //  * | Operation : Reopening of the application
    //  */
    // public function putReopenComplaintById(Request $req, $id)
    // {
    //     try {
    //         DB::beginTransaction();
    //         DB::table('grievance_application AS t') //<---------- here(CAUTION)
    //             ->find($id)
    //             ->update([
    //                 't.complaint_response_reason' => $req->complaintReopenReason,
    //                 // 't.complain_reopen_image'=>$req->images,
    //                 't.complaint_reopen_additional_details' => $req->complainReopenAdditionalDetails,
    //                 't.complaint_reopen_count' => 't.complaint_reopen_count' + 1
    //             ]);
    //         DB::commit();
    //         $readApplicationDetailList = DB::table('grievance_application AS t') //<----------- here(CAUTION)
    //             ->find($id)
    //             ->get();
    //         return responseMsg(true, "reopening detail", $readApplicationDetailList);
    //     } catch (Exception $e) {
    //         return $e;
    //     }
    // }
}
