<?php

namespace App\Http\Requests\Trade;

use App\Repository\Common\CommonFunction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class ReqCitizenAddRecorde extends TradeRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $mApplicationTypeId = Config::get("TradeConstant.APPLICATION-TYPE." . $this->applicationType);
        $mNowdate = Carbon::now()->format('Y-m-d');
        $mRegex = '/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/';
        $mFramNameRegex = '/^[a-zA-Z1-9][a-zA-Z1-9\.&\s]+$/';
        $reftrade = new CommonFunction();
        $refWorkflowId = Config::get('workflow-constants.TRADE_WORKFLOW_ID');
        $mUserType = $reftrade->userType($refWorkflowId);
        $rules = [];
        $rules["ulbId"]="required|digits_between:1,92";
        $rules["applicationType"]="required|string|in:NEWLICENSE,RENEWAL,AMENDMENT,SURRENDER";
        if($this->applicationType!="NEWLICENSE")
        {
            $rules["id"] ="required|digits_between:1,9223372036854775807";
        }
        if (in_array($mApplicationTypeId, [1])) 
        {
            $rules["firmDetails.areaSqft"] = "required|numeric";
            $rules["firmDetails.businessAddress"] = "required|regex:$mRegex";
            $rules["firmDetails.businessDescription"] = "required|regex:$mRegex";
            $rules["firmDetails.firmEstdDate"] = "required|date";
            $rules["firmDetails.firmName"] = "required|regex:$mFramNameRegex";
            $rules["firmDetails.premisesOwner"] = "required|regex:/^[a-zA-Z1-9][a-zA-Z1-9\., \s]+$/";
            $rules["firmDetails.natureOfBusiness"] = "required|array";
            $rules["firmDetails.natureOfBusiness.*.id"] = "required|digits_between:1,9223372036854775807";
            $rules["firmDetails.newWardNo"] = "required|digits_between:1,9223372036854775807";
            $rules["firmDetails.wardNo"] = "required|digits_between:1,9223372036854775807";
            $rules["firmDetails.tocStatus"] = "required|bool";
            $rules["firmDetails.landmark"] = "regex:$mRegex";
            $rules["firmDetails.k_no"] = "digits|regex:/[0-9]{10}/";
            $rules["firmDetails.bind_book_no"] = "regex:$mRegex";
            $rules["firmDetails.account_no"] = "regex:$mRegex";
            $rules["firmDetails.pincode"] = "digits:6|regex:/[0-9]{6}/";

            $rules["initialBusinessDetails.applyWith"] = "required|digits_between:1,9223372036854775807";
            $rules["initialBusinessDetails.firmType"] = "required|digits_between:1,9223372036854775807";
            $rules["initialBusinessDetails.categoryTypeId"] = "digits_between:1,9223372036854775807";
            if (isset($this->initialBusinessDetails['firmType']) && $this->initialBusinessDetails['firmType'] == 5) 
            {
                $rules["initialBusinessDetails.otherFirmType"] = "required|regex:$mRegex";
            }
            $rules["initialBusinessDetails.ownershipType"] = "required|digits_between:1,9223372036854775807";
            if (isset($this->initialBusinessDetails['applyWith']) && $this->initialBusinessDetails['applyWith'] == 1) {
                $rules["initialBusinessDetails.noticeNo"] = "required";
                $rules["initialBusinessDetails.noticeDate"] = "required|date";
            }
            $rules["licenseDetails.licenseFor"] = "required|int";
            if ($mApplicationTypeId != 4 && strtoupper($mUserType) != "ONLINE") {
                $rules["licenseDetails.totalCharge"] = "required|numeric";
            }
            if (isset($this->firmDetails["tocStatus"]) && $this->firmDetails["tocStatus"]) 
            {
                $rules["licenseDetails.licenseFor"] = "required|int|max:1";
            }
            if (in_array(strtoupper($mUserType), ["JSK", "UTC", "TC", "SUPER ADMIN", "TL"])) 
            {
                $rules["licenseDetails.paymentMode"] = "required|alpha";
                if (isset($this->licenseDetails['paymentMode']) && $this->licenseDetails['paymentMode'] != "CASH") 
                {
                    $rules["licenseDetails.chequeNo"] = "required";
                    $rules["licenseDetails.chequeDate"] = "required|date|date_format:Y-m-d|after_or_equal:$mNowdate";
                    $rules["licenseDetails.bankName"] = "required|regex:$mRegex";
                    $rules["licenseDetails.branchName"] = "required|regex:$mRegex";
                }
            }

            $rules["ownerDetails"] = "required|array";
            $rules["ownerDetails.*.businessOwnerName"] = "required|regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9]+)*$/";
            $rules["ownerDetails.*.guardianName"] = "regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9]+)*$/|nullable";
            $rules["ownerDetails.*.mobileNo"] = "required|digits:10|regex:/[0-9]{10}/";
            $rules["ownerDetails.*.email"] = "email|nullable";
        } 
        elseif (in_array($mApplicationTypeId, [2, 4])) # 2- Renewal,4- Surender
        {
            $rules["firmDetails.holdingNo"] = "required";
            
            if ($mApplicationTypeId == 2) {
                $rules["licenseDetails.licenseFor"] = "required|int";
                if (isset($this->firmDetails["tocStatus"]) && $this->firmDetails["tocStatus"]) {
                    $rules["licenseDetails.licenseFor"] = "required|int|max:1";
                }
            }
            if ($mApplicationTypeId != 4 && strtoupper($mUserType) != "ONLINE") {
                $rules["licenseDetails.totalCharge"] = "required|numeric";
            }
            if (in_array(strtoupper($mUserType), ["JSK", "UTC", "TC", "SUPER ADMIN", "TL"]) && $mApplicationTypeId == 2) {
                $rules["licenseDetails.paymentMode"] = "required|alpha";
                if (isset($this->licenseDetails['paymentMode']) && $this->licenseDetails['paymentMode'] != "CASH") {
                    $rules["licenseDetails.chequeNo"] = "required";
                    $rules["licenseDetails.chequeDate"] = "required|date|date_format:Y-m-d|after_or_equal:$mNowdate";
                    $rules["licenseDetails.bankName"] = "required|regex:$mRegex";
                    $rules["licenseDetails.branchName"] = "required|regex:$mRegex";
                }
            }
        } 
        elseif (in_array($mApplicationTypeId, [3])) # 3- Amendment
        {
            $rules["firmDetails.areaSqft"] = "required|numeric";
            //$rules["firmDetails.businessAddress"]="required|regex:$mRegex";
            $rules["firmDetails.businessDescription"] = "required|regex:$mRegex";
            // $rules["firmDetails.firmName"]="required|regex:$mFramNameRegex";
            $rules["firmDetails.holdingNo"] = "required";
            $rules["initialBusinessDetails.ownershipType"] = "required|digits_between:1,9223372036854775807";
            $rules["licenseDetails.licenseFor"] = "required|int";
            $rules["initialBusinessDetails.firmType"] = "required|digits_between:1,9223372036854775807";
            if (isset($this->initialBusinessDetails['firmType']) && $this->initialBusinessDetails['firmType'] == 5) {
                $rules["initialBusinessDetails.otherFirmType"] = "required|regex:$mRegex";
            }
            if ($mApplicationTypeId != 4 && strtoupper($mUserType) != "ONLINE") {
                $rules["licenseDetails.totalCharge"] = "required|numeric";
            }
            if (in_array(strtoupper($mUserType), ["JSK", "UTC", "TC", "SUPER ADMIN", "TL"])) {
                $rules["licenseDetails.paymentMode"] = "required|alpha";
                if (isset($this->licenseDetails['paymentMode']) && $this->licenseDetails['paymentMode'] != "CASH") {
                    $rules["licenseDetails.chequeNo"] = "required";
                    $rules["licenseDetails.chequeDate"] = "required|date|date_format:Y-m-d|after_or_equal:$mNowdate";
                    $rules["licenseDetails.bankName"] = "required|regex:$mRegex";
                    $rules["licenseDetails.branchName"] = "required|regex:$mRegex";
                }
            }
            $rules["ownerDetails"] = "array";
            if ($this->ownerDetails) {
                $rules["ownerDetails.*.businessOwnerName"] = "required|regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9]+)*$/";
                $rules["ownerDetails.*.guardianName"] = "regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9]+)*$/|nullable";
                $rules["ownerDetails.*.mobileNo"] = "required|digits:10|regex:/[0-9]{10}/";
                $rules["ownerDetails.*.email"] = "email|nullable";
            }
        }
        return $rules;
    }
}