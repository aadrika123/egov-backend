<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class DemoController extends Controller
{

    public function waterConnection(Request $req)
    {
        $data = User::select('application_no', 'ward_id')
            ->join('water_applications', 'water_applications.user_id', 'users.id')
            ->where('users.mobile', $req->mobileNo)
            ->get();
        return $data;
    }

    public function sendSms(Request $req)
    {
        $mobile     = "8797770238";
        // $sms        = "Thank you " . $ownerName . " for making payment of Rs. " . $paidAmount . " against Property No. " . $propertyNo . ". For more details visit www.akolamc.org/call us at:18008907909 SWATI INDUSTRIES";
        $message    = "OTP for Citizen Registration of sdskdmks is 898989. This OTP is valid for 10 minutes. For more info call us 1800123123.-UD&HD, GOJ";
        $templateid = "1307171162976397795";
        $data = send_sms($mobile, $message, $templateid);
        return $data;
    }
}
