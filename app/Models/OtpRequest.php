<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpRequest extends Model
{
    use HasFactory;

    /**
     * | Save the Otp for Checking Validatin
     * | @param 
     */
    public function saveOtp($request, $generateOtp)
    {
        $mOtpMaster = new OtpRequest();
        $mOtpMaster->mobile_no   = $request->mobileNo;
        $mOtpMaster->otp         = $generateOtp;
        $mOtpMaster->otp_time    = Carbon::now();
        $mOtpMaster->save();
    }

    /**
     * | Check the OTP in the data base 
     * | @param 
     */
    public function checkOtp($request)
    {
        return OtpRequest::where('otp', $request->otp)
            ->where('mobile_no', $request->mobileNo)
            ->first();
    }
}
