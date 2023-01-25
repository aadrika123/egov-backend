<?php

namespace App\Http\Controllers;

use App\Models\ActiveCitizen;
use Illuminate\Http\Request;
use App\Repository\Citizen\iCitizenRepository;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

/**
 * | Created On-08-08-2022 
 * | Created By-Anshu Kumar
 * --------------------------------------------------------------------------------------------
 * | Citizens Operations for Save, approve,Reject
 */
class CitizenController extends Controller
{
    // Initializing Repository
    protected $repository;

    public function __construct(iCitizenRepository $repository)
    {
        $this->Repository = $repository;
    }

    // Citizen Registrations
    public function citizenRegister(Request $request)
    {
        $request->validate([
            'name'     => 'required',
            'mobile'   => 'required|numeric|digits:10',
            'password' => [
                'required',
                'min:6',
                'max:255',
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/'  // must contain a special character
            ],
        ]);

        try {
            $mCitizen = new ActiveCitizen();
            $citizens = $mCitizen->getCitizenByMobile($request->mobile);
            if (isset($citizens))
                return responseMsgs(false, "This Mobile No is Already Existing", "");
            $mCitizen->citizenRegister($request);
            return responseMsg(true, "Succesfully Registered", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     *  Citizen Login
     */
    public function citizenLogin(Request $req)
    {
        try {
            $req->validate([
                'mobile' => "required",
                'password' => [
                    'required',
                    'min:6',
                    'max:255',
                    'regex:/[a-z]/',      // must contain at least one lowercase letter
                    'regex:/[A-Z]/',      // must contain at least one uppercase letter
                    'regex:/[0-9]/',      // must contain at least one digit
                    'regex:/[@$!%*#?&]/'  // must contain a special character
                ],
            ]);
            $citizenInfo = ActiveCitizen::where('mobile', $req->mobile)
                ->first();
            if (!$citizenInfo) {
                $msg = "Oops! Given mobile no does not exist";
                return responseMsg(false, $msg, "");
            }

            $userDetails['userName'] = $citizenInfo->user_name;
            $userDetails['mobile'] = $citizenInfo->mobile;
            $userDetails['userType'] = $citizenInfo->user_type;

            if ($citizenInfo) {
                if (Hash::check($req->password, $citizenInfo->password)) {
                    $token = $citizenInfo->createToken('my-app-token')->plainTextToken;
                    $citizenInfo->remember_token = $token;
                    $citizenInfo->save();
                    $userDetails['token'] = $token;
                    return responseMsgs(true, 'You r logged in now', $userDetails, '', "1.0", "494ms", "POST", "");
                } else {
                    $msg = "Incorrect Password";
                    return responseMsg(false, $msg, '');
                }
            }
        }
        // Authentication Using Sql Database
        catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Citizen Logout 
     */
    public function citizenLogout(Request $req)
    {
        // token();
        $id =  auth()->user()->id;

        $user = ActiveCitizen::where('id', $id)->first();
        $user->remember_token = null;
        $user->save();

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }




    // Get Citizen By ID
    public function getCitizenByID($id)
    {
        return $this->Repository->getCitizenByID($id);
    }

    // Get All Citizens
    public function getAllCitizens()
    {
        return $this->Repository->getAllCitizens();
    }

    // Update or Reject Citizen By id
    public function editCitizenByID(Request $request, $id)
    {
        return $this->Repository->editCitizenByID($request, $id);
    }

    // Get all applications
    public function getAllAppliedApplications(Request $req)
    {
        return $this->Repository->getAllAppliedApplications($req);
    }

    // Independent Comment
    public function commentIndependent(Request $req)
    {
        $req->validate([
            'message' => 'required|string'
        ]);

        return $this->Repository->commentIndependent($req);
    }

    // Citizen Transaction History
    public function getTransactionHistory()
    {
        return $this->Repository->getTransactionHistory();
    }
}
