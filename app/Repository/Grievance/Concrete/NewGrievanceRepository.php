<?php

namespace App\Repository\Grievance\Concrete;

use App\Repository\Grievance\Interfaces\iGrievance;
use App\Models\Grievance\GrievanceCompApplication;
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

    public function saveFileComplain(Request $request)
    {
        $validateUser = Validator::make(
            $request->all(),
            [
                'wardId'                 => 'required',
                // 'complaintType'          => 'required',
                // 'complaintSubType'       => 'required',
                'complaintCity'          => 'required',
                'complaintLocality'      => 'required',
            ]
        );

        if ($validateUser->fails()) {
            return responseMsg(false, $validateUser->errors(), "");
        }

        DB::beginTransaction();
        try {
            $newApplication = new GrievanceCompApplication();
            // $newApplication->comp_type_id = $request->complaintType;
            // $newApplication->comp_sub_type_id = $request->complaintSubType;
            $newApplication->comp_pincode = $request->complaintPincode;
            $newApplication->comp_city = $request->complaintCity;
            $newApplication->comp_locality = $request->complaintLocality;
            $newApplication->ward_no = $request->complaintWardNo;
            $newApplication->comp_house_no = $request->complaintHouseNo;
            $newApplication->comp_landmark = $request->complaintLandmark;
            // $newApplication->comp_image = $request->complainImage; //<-------------- here 
            $newApplication->comp_additional_details = $request->complaintAdditionalDetails;
            $newApplication->comp_comment = $request->complaintComment;

            // Generating Application No/ Complaint No
            $now = Carbon::now();
            $applicationtNo = 'APP/grievance' . $now->getTimeStamp();    //<-------------- here
            $complaintNo = 'PG-PGR' . date("-Y") . date("-d-m") . sprintf("%06d", mt_rand(1, 999999)); //<-------------- here
            $newApplication->comp_no = $complaintNo;
            $newApplication->comp_application_no = $applicationtNo;

            // $newApplication->ulb_id = auth()->user()->ulb_id;
            // $newApplication->citizen_id = auth()->user()->id;
            // $newApplication->user_id = auth()->user()->id;
            $newApplication->save();

            // DB::commit(); //<-------------- here (CAUTION)

            $data = ["complaintNo" => $complaintNo, "complaintDate" => date("d-m-y"), "complaintApplicationStatus" => 0];
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
        try {
            $readApplicationData = GrievanceCompApplication::select(
                'created_at AS complaintFileDate',
                'comp_status AS complainStatus',
                'comp_no AS complainNo',
                'comp_status AS complaintApplictionStatus',
                // 't.comp_type AS complainType',
                // 't.comp_sub_type AS complainSubType',
                'comp_pincode AS compalinPincode',
                'comp_city AS complainCity',
                'comp_locality AS complainLocality',
                'ward_no AS wardNo',
                'comp_house_no AS houseNo',
                'comp_landmark AS Landmark',
                'comp_additional_details AS complainAdditionalDetails'
            )->where('id', $id)
                ->get();

            if ($readApplicationData->isEmpty()) {
                return responseMsg(false, "invalid id NO data !", "");
            }
            return responseMsg(true, "data fetched !", $readApplicationData);
        } catch (Exception $e) {
            return $e;
        }
    }

    // /**
    //  * | code : Sam Kerketta
    //  * | ----------------- Get Connection Through  ------------------------------- |
    //  * | @param req
    //  * | @param id
    //  * | @var readApplicationDetail 
    //  * | Operation : adding rating to the application
    //  */
    public function updateRateComplaintById(Request $req, $id)
    {
        try {
            GrievanceCompApplication::where('id', $id)
                ->update([
                    'comp_rating' => $req->complaintRate,
                    'comp_remark' => $req->complaintRemark,
                    'comp_comment' => $req->complaintComment
                ]);
            DB::commit(); //<----this(CAUTION)
            return responseMsg(true, "rated Success!", "");
        } catch (Exception $e) {
            return $e;
        }
    }

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
    //         // DB::beginTransaction();
    //         // $a=DB::table('grievance_comp_applications')
    //         //     ->where('id',$id)
    //         //     ->select('comp_rating')
    //         //     ->get();
    //         // return($a['0']->comp_rating);
    //         DB::beginTransaction();
    //         GrievanceCompApplication::find($id)
    //             ->update([
    //                 'complaint_response_reason' => $req->complaintReopenReason, //<------ operation after the reopen dta save
    //                 // 't.complain_reopen_image'=>$req->images,
    //                 'complaint_reopen_additional_details' => $req->complainReopenAdditionalDetails,
    //                 'complaint_reopen_count' => 'complaint_reopen_count' + 1,
    //                 'comp_status' => 0
    //             ]);
    //         // DB::commit(); //<-------- here (look)
    //         $readApplicationDetailList = DB::table('grievance_comp_applications AS t') //<----------- here(CAUTION)
    //             ->find($id)
    //             ->get();
    //         return responseMsg(true, "reopening detail", $readApplicationDetailList);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return $e;
    //     }
    // }
}
