<?php

namespace App\Http\Controllers;

use App\MicroServices\IdGeneration;
use App\Models\ActiveCitizen;
use App\Models\OtpMaster;
use App\Models\OtpRequest;
use App\Models\User;
use Seshac\Otp\Otp;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use WpOrg\Requests\Auth;

class ThirdPartyController extends Controller
{
    // OTP related Operations


    /**
     * | Send OTP for Use
     * | OTP for Changing PassWord using the mobile no 
     * | @param request
     * | @var 
     * | @return 
        | Serial No : 01
        | Not Checked
        | Dont share otp 
     */
    public function sendOtp(Request $request)
    {
        try {
            $request->validate([
                'mobileNo' => "required|digits:10|regex:/[0-9]{10}/", #exists:active_citizens,mobile|
                'type' => "nullable|in:Register",
            ]);
            $refIdGeneration = new IdGeneration();
            $mOtpRequest = new OtpRequest();
            if ($request->type == "Register") {
                $userDetails = ActiveCitizen::where('mobile', $request->mobileNo)
                    ->first();
                if ($userDetails) {
                    throw new Exception("Respective mobile no $request->mobileNo is Registered to An Existing Account!");
                }
            }
            $generateOtp = $refIdGeneration->generateOtp();
            DB::beginTransaction();
            $mOtpRequest->saveOtp($request, $generateOtp);
            DB::commit();
            return responseMsgs(true, "OTP send to your mobile No!", $generateOtp, "", "01", ".ms", "POST", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "0101", "01", ".ms", "POST", "");
        }
    }

    /**
     * | Verify OTP 
     * | Check OTP and Create a Token
     * | @param request
        | Serial No : 02
        | Not Checked
     */
    public function verifyOtp(Request $request)
    {
        try {
            $request->validate([
                'otp' => "required|digits:6",
                'mobileNo' => "required|digits:10|regex:/[0-9]{10}/"
            ]);
            $mOtpMaster = new OtpRequest();
            $mActiveCitizen = new ActiveCitizen();
            $checkOtp = $mOtpMaster->checkOtp($request);
            if (!$checkOtp) {
                $msg = "Oops! Given OTP or mobileNo dosent match!";
                return responseMsgs(false, $msg, "", "", "01", ".ms", "POST", "");
            }
            $token = $mActiveCitizen->changeToken($request);
            $checkOtp->delete();
            return responseMsgs(true, "OTP Validated!", remove_null($token), "", "01", ".ms", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", "");
        }
    }
}
