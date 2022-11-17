<?php

namespace App\Repository\Citizen;

use Illuminate\Http\Request;
use App\Repository\Citizen\iCitizenRepository;
use App\Models\ActiveCitizen;
use App\Models\Property\ActiveSaf;
use App\Models\Trade\ActiveLicence;
use App\Models\User;
use App\Models\Water\WaterApplication;
use App\Models\WorkflowTrack;
use App\Traits\Auth;
use App\Traits\Workflow\Workflow;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * | Created On-08-08-2022 
 * | Created By-Anshu Kumar.
 * -------------------------------------------------------------------------------------------
 * | Eloquent For Citizen Registration and Approval
 */

class CitizenRepository implements iCitizenRepository
{
    private $_appliedApplications;
    private $_redis;

    use Auth, Workflow;

    public function __construct()
    {
        $this->_redis = Redis::connection();
    }

    /**
     * | Citizen Register
     * | @param Request 
     * | @param Request $request
     * --------------------------------------------------------------------------------------
     * | Validation First 
     * | Save On Database
     */
    public function citizenRegister(Request $request)
    {

        $validator = Validator::make(request()->all(), [
            'name'     => 'required',
            'mobile' => 'required',
            'email' => 'required|unique:users',
            'password' => [
                'required',
                'min:6',
                'max:255',
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/'  // must contain a special character
            ],
            'ulb' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(
                $validator->errors(),
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $citizen = new ActiveCitizen;
            $citizen->user_name = $request->name;
            $citizen->mobile = $request->mobile;
            $citizen->email = $request->email;
            $citizen->password = Hash::make($request->password);
            $citizen->user_type = $request->userType;
            $citizen->ulb_id = $request->ulb;
            $citizen->save();
            return response()->json('Successfully Registered', 200);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * | Get Citizens by ID
     * | @param Citizen-id $id
     * | join with ulb_masters
     */
    public function getCitizenByID($id)
    {
        $citizen = ActiveCitizen::select('active_citizens.id', 'user_name', 'mobile', 'email', 'user_type', 'ulb_id', 'is_approved', 'ulb_name')
            ->where('active_citizens.id', $id)
            ->leftJoin('ulb_masters', 'active_citizens.ulb_id', '=', 'ulb_masters.id')
            ->first();
        return $citizen;
    }

    /**
     * | Get All Citizens
     * | Join With ulb_masters
     */
    public function getAllCitizens()
    {
        $citizen = ActiveCitizen::select('active_citizens.id', 'user_name', 'mobile', 'email', 'user_type', 'ulb_id', 'is_approved', 'ulb_name')
            ->where('is_approved', null)
            ->leftJoin('ulb_masters', 'active_citizens.ulb_id', '=', 'ulb_masters.id')
            ->get();
        return $citizen;
    }

    /**
     * | Approve Or Reject Citizen by ID
     * | Validation first if the Status has been Selected or Not
     * | If is_approved is true then it will export on users table
     * | If is_approved is false then is_approved field get false
     */
    public function editCitizenByID(Request $request, $id)
    {
        $validator = Validator::make(request()->all(), [
            'isApproved'     => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(
                $validator->errors(),
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $citizen = ActiveCitizen::find($id);
            $citizen->is_approved = $request->isApproved;
            $citizen->created_by = auth()->user()->id;
            $citizen->save();
            if ($request->isApproved == '1') {
                $user = new User;
                $user->user_name = $citizen->user_name;
                $user->mobile = $citizen->mobile;
                $user->email = $citizen->email;
                $user->password = $citizen->password;
                $user->user_type = 'Citizen';
                $user->ulb_id = $citizen->ulb_id;
                $token = Str::random(80);                       //Generating Random Token for Initial
                $user->remember_token = $token;
                $user->save();
            }
            return response()->json('Successful', 200);
        } catch (Exception $e) {
            return response()->json('Something Went Wrong', 400);
        }
    }

    /**
     * | Get All Applied Applications of All Modules
     */
    public function getAllAppliedApplications($req)
    {
        $userId = auth()->user()->id;
        $applications = [];

        if ($req->getMethod() == 'GET') {                                                       // For All Applications
            $applications['Property'] = $this->appliedSafApplications($userId);
            $applications['Water'] = $this->appliedWaterApplications($userId);
            $applications['Trade'] = $this->appliedTradeApplications($userId);
        }

        if ($req->getMethod() == 'POST') {                                                      // Get Applications By Module
            if ($req->module == 'Property') {
                $applications['Property'] = $this->appliedSafApplications($userId);
            }

            if ($req->module == 'Water') {
                $applications['Water'] = $this->appliedWaterApplications($userId);
            }

            if ($req->module == 'Trade') {
                $applications['Trade'] = $this->appliedTradeApplications($userId);
            }
        }

        $totalApplications = collect($applications)->map(function ($application) {              // collection total applications sum of all modules
            return $application['totalApplications'];
        });

        $applications['totalApplications'] = $totalApplications->sum();

        return responseMsg(true, "All Applied Applications", remove_null($applications));
    }

    /**
     * | Applied Saf Applications
     * | Redis Should Be delete on SAF Apply, Trade License Apply and Water Apply
     * | Status - Closed
     */
    public function appliedSafApplications($userId)
    {
        $applications = array();
        $propertyApplications = DB::table('active_safs')
            ->select('id as application_id', 'saf_no', 'holding_no', 'assessment_type', 'application_date', 'payment_status', 'saf_pending_status', 'created_at', 'updated_at')
            ->where('user_id', $userId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();

        $applications['applications'] = $propertyApplications;
        $applications['totalApplications'] = $propertyApplications->count();                // Total Applications
        return collect($applications)->reverse();
    }

    /**
     * | Applied Water Applications
     * | Status-Closed
     */
    public function appliedWaterApplications($userId)
    {
        $applications = array();
        $waterApplications = WaterApplication::select('id as application_id', 'category', 'application_no', 'holding_no', 'created_at', 'updated_at')
            ->where('citizen_id', $userId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();
        $applications['applications'] = $waterApplications;
        $applications['totalApplications'] = $waterApplications->count();
        return collect($applications)->reverse();
    }

    /**
     * | Applied Trade Applications
     * | Status- Closed
     */
    public function appliedTradeApplications($userId)
    {
        $applications = array();
        $tradeApplications = ActiveLicence::select('id as application_id', 'application_no', 'holding_no', 'created_at', 'updated_at')
            ->where('emp_details_id', $userId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();
        $applications['applications'] = $tradeApplications;
        $applications['totalApplications'] = $tradeApplications->count();
        return collect($applications)->reverse();
    }

    /**
     * | Independent Comment for the Citizen on their applications
     * | @param req requested parameters
     * | Status-Closed
     */
    public function commentIndependent($req)
    {
        $array = array();
        $array['workflowId'] = $req->workflowId;
        $array['citizenId'] = auth()->user()->id;
        $array['refTableId'] = $req->refTableId;
        $array['applicationId'] = $req->applicationId;
        $array['message'] = $req->message;

        $workflowTrack = new WorkflowTrack();
        $this->workflowTrack($workflowTrack, $array);                                                                   // Trait For Workflow Track
        $workflowTrack->save();
        return responseMsg(true, "Successfully Given the Message", "");
    }
}
