<?php

namespace App\Http\Controllers;

use App\Models\ActiveCitizen;
use App\Models\Citizen\ActiveCitizenUndercare;
use App\Models\Water\WaterApprovalApplicant;
use App\Models\Water\WaterConsumer;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            $mWaterConsumer = new WaterConsumer();

            $waterDtl = $mWaterConsumer->getConsumerByNo($req->consumerNo);
            if (!isset($waterDtl))
                throw new Exception('Water Connection Not Found!');

            $approveApplicant = $mWaterApprovalApplicant->getOwnerDtlById($waterDtl->apply_connection_id);
            $applicantMobile = $approveApplicant->mobile_no;

            $myRequest = new \Illuminate\Http\Request();
            $myRequest->setMethod('POST');
            $myRequest->request->add(['mobileNo' => $applicantMobile]);
            $otpResponse = $ThirdPartyController->sendOtp($myRequest);

            $response = collect($otpResponse)->toArray();
            $data = [
                'otp' => $response['original']['data'],
                'mobileNo' => $applicantMobile
            ];

            return responseMsgs(true, "OTP send successfully", $data, '', '01', '623ms', 'Post', '');
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
            'otp' => 'required|digits:6'
        ]);
        try {
            $userId = authUser()->id;
            $mWaterApprovalApplicant = new WaterApprovalApplicant();
            $mActiveCitizenUndercare = new ActiveCitizenUndercare();
            $mWaterConsumer = new WaterConsumer();
            $ThirdPartyController = new ThirdPartyController();

            DB::beginTransaction();
            $waterDtl = $mWaterConsumer->getConsumerByNo($req->consumerNo);
            if (!isset($waterDtl))
                throw new Exception('Water Connection Not Found!');
            $approveApplicant = $mWaterApprovalApplicant->getOwnerDtlById($waterDtl->apply_connection_id);

            $myRequest = new \Illuminate\Http\Request();
            $myRequest->setMethod('POST');
            $myRequest->request->add(['mobileNo' => $approveApplicant->mobile_no]);
            $myRequest->request->add(['otp' => $req->otp]);
            $otpReturnData = $ThirdPartyController->verifyOtp($myRequest);
            $verificationStatus = collect($otpReturnData)['original']['status'];
            if ($verificationStatus == false)
                throw new Exception("otp Not Validated!");

            $existingData = $mActiveCitizenUndercare->getDetailsForUnderCare($userId, $waterDtl->id);
            if (!isset($existingData))
                throw new Exception("ConsumerNo caretaker already exist!");

            $mActiveCitizenUndercare->saveCaretakeDetails($waterDtl->id, $approveApplicant->mobile_no, $userId);
            DB::commit();
            return responseMsgs(true, "Cosumer Succesfully Attached!", '', '', '01', '623ms', 'Post', '');
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
