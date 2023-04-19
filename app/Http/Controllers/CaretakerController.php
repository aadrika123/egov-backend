<?php

namespace App\Http\Controllers;

use App\Models\ActiveCitizen;
use App\Models\Citizen\ActiveCitizenUndercare;
use App\Models\Water\WaterApprovalApplicant;
use App\Models\Water\WaterConsumer;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

/**
 * | Created On-19-04-2022 
 * | Created By-Mrinal Kumar
 */

class CaretakerController extends Controller
{
    /**
     * | Send otp for caretaker property
     */
    public function waterCaretakerOtp(Request $req)
    {
        try {
            $mWaterApprovalApplicant = new WaterApprovalApplicant();
            $ThirdPartyController = new ThirdPartyController();
            $waterDtl = WaterConsumer::where('consumer_no', $req->consumerNo)
                ->first();

            if (!isset($waterDtl))
                throw new Exception('No Water Connection Not Found');
            $approveApplicant = $mWaterApprovalApplicant->getOwnerDtlById($waterDtl->apply_connection_id);
            $applicantMobile = $approveApplicant->mobile_no;

            $myRequest = new \Illuminate\Http\Request();
            $myRequest->setMethod('POST');
            $myRequest->request->add(['mobileNo' => $applicantMobile]);
            $response = $ThirdPartyController->sendOtp($myRequest);

            $response = collect($response)->toArray();
            $data['otp'] = $response['original']['data'];
            $data['mobileNo'] = $applicantMobile;

            return responseMsgs(true, "OTP send successfully", $data, '010801', '01', '623ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Care taker property tag
     */
    public function caretakerConsumerTag(Request $req)
    {
        $req->validate([
            'consumerNo' => 'required|max:255',
        ]);
        try {
            $userId = authUser()->id;
            $mWaterApprovalApplicant = new WaterApprovalApplicant();
            $activeCitizenUndecare = new ActiveCitizenUndercare();
            $activeCitizen = ActiveCitizen::find($userId);

            $waterDtl = WaterConsumer::where('consumer_no', $req->consumerNo)
                ->first();

            if (!isset($waterDtl))
                throw new Exception('No Water Connection Not Found');
            $approveApplicant = $mWaterApprovalApplicant->getOwnerDtlById($waterDtl->apply_connection_id);

            $activeCitizenUndecare->consumer_id = $waterDtl->id;
            $activeCitizenUndecare->date_of_attachment = Carbon::now();
            $activeCitizenUndecare->mobile_no = $approveApplicant->mobile_no;
            $activeCitizenUndecare->citizen_id = $userId;
            $activeCitizenUndecare->save();

            return responseMsgs(true, "Property Tagged!", '', '010801', '01', '623ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
