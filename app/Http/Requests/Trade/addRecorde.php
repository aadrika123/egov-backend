<?php

namespace App\Http\Requests\Trade;

use App\Repository\Common\CommonFunction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class addRecorde extends FormRequest
{
    
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    { 
        if($this->getMethod()=='GET')
            return [];
        $mApplicationTypeId = Config::get("TradeConstant.APPLICATION-TYPE.".$this->applicationType);
        $mNowdate = Carbon::now()->format('Y-m-d'); 
        $mTimstamp = Carbon::now()->format('Y-m-d H:i:s');                
        $mRegex = '/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/';
        $mFramNameRegex = '/^[a-zA-Z1-9][a-zA-Z1-9\.&\s]+$/';
        $mAlphaNumCommaSlash='/^[a-zA-Z0-9- ]+$/i';
        $mAlphaSpace ='/^[a-zA-Z ]+$/i';
        $mAlphaNumhyphen ='/^[a-zA-Z0-9- ]+$/i';
        $mNumDot = '/^\d+(?:\.\d+)+$/i';
        $mDateFormatYYYMMDD ='/^([12]\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))+$/i';
        $mDateFormatYYYMM='/^([12]\d{3}-(0[1-9]|1[0-2]))+$/i';
        $reftrade = new CommonFunction();
        $mUserType = $reftrade->userType();
        $rules = [];
        if(in_array($mApplicationTypeId,[1]))
        {
            $rules["firmDetails.areaSqft"]="required|numeric";
            $rules["firmDetails.businessAddress"]="required|regex:$mRegex";
            $rules["firmDetails.businessDescription"]="required|regex:$mRegex"; 
            $rules["firmDetails.firmEstdDate"]="required|date"; 
            $rules["firmDetails.firmName"]="required|regex:$mFramNameRegex";
            $rules["firmDetails.premisesOwner"]="required|regex:$mRegex";
            $rules["firmDetails.natureOfBusiness"]="required|array";
            $rules["firmDetails.natureOfBusiness.*.id"]="required|int";
            $rules["firmDetails.newWardNo"]="required|int";
            $rules["firmDetails.wardNo"]="required|int";
            $rules["firmDetails.tocStatus"] = "required|bool";
            $rules["firmDetails.landmark"]="regex:$mRegex";
            $rules["firmDetails.categoryTypeId"]="int";
            $rules["firmDetails.k_no"] = "digits|regex:/[0-9]{10}/";
            $rules["firmDetails.bind_book_no"] = "regex:$mRegex";
            $rules["firmDetails.account_no"] = "regex:$mRegex";
            if(strtoupper($mUserType)=="ONLINE")
            {
                $rules["firmDetails.pincode"]="digits:6|regex:/[0-9]{6}/";                    
            }               
            
            $rules["initialBusinessDetails.applyWith"]="required|int";
            $rules["initialBusinessDetails.firmType"]="required|int";
            if(isset($this->initialBusinessDetails['firmType']) && $this->initialBusinessDetails['firmType']==5)
            {
                $rules["initialBusinessDetails.otherFirmType"]="required|regex:$mRegex";
            }
            $rules["initialBusinessDetails.ownershipType"]="required|int";
            if( isset($this->initialBusinessDetails['applyWith']) && $this->initialBusinessDetails['applyWith']==1)
            {
                $rules["initialBusinessDetails.noticeNo"]="required";
                $rules["initialBusinessDetails.noticeDate"]="required|date";  
            }
            $rules["licenseDetails.licenseFor"]="required|int";
            if($mApplicationTypeId!=4 && strtoupper($mUserType)!="ONLINE")
            {
                $rules["licenseDetails.totalCharge"] = "required|numeric";
            }
            if(isset($this->firmDetails["tocStatus"]) && $this->firmDetails["tocStatus"])
            {
                $rules["licenseDetails.licenseFor"]="required|int|max:1";
            }
            if(in_array(strtoupper($mUserType),["JSK","UTC","TC","SUPER ADMIN","TL"]))
            {
                $rules["licenseDetails.paymentMode"]="required|alpha"; 
                if(isset($this->licenseDetails['paymentMode']) && $this->licenseDetails['paymentMode']!="CASH")
                {
                    $rules["licenseDetails.chequeNo"] ="required";
                    $rules["licenseDetails.chequeDate"] ="required|date|date_format:Y-m-d|after_or_equal:$mNowdate";
                    $rules["licenseDetails.bankName"] ="required|regex:$mRegex";
                    $rules["licenseDetails.branchName"] ="required|regex:$mRegex";
                } 
            }

            $rules["ownerDetails"] = "required|array";
            $rules["ownerDetails.*.businessOwnerName"]="required|regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9]+)*$/";
            $rules["ownerDetails.*.guardianName"]="regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9]+)*$/";
            $rules["ownerDetails.*.mobileNo"]="required|digits:10|regex:/[0-9]{10}/";
            $rules["ownerDetails.*.email"]="email";
            
            
        }
        elseif(in_array($mApplicationTypeId, [2,4])) # 2- Renewal,4- Surender
        {
            // if (in_array($application_type_id, ["2"])) 
            {                    
                $rules["firmDetails.holdingNo"]="required";
            } 
            $rules["licenseDetails.licenseFor"]="required|int";
            if(isset($this->firmDetails["tocStatus"]) && $this->firmDetails["tocStatus"])
            {
                $rules["licenseDetails.licenseFor"]="required|int|max:1";
            }
            if($mApplicationTypeId!=4 && strtoupper($mUserType)!="ONLINE")
            {
                $rules["licenseDetails.totalCharge"] = "required|numeric";
            }
            if(in_array(strtoupper($mUserType),["JSK","UTC","TC","SUPER ADMIN","TL"]) && $mApplicationTypeId==2)
            {
                $rules["licenseDetails.paymentMode"]="required|alpha"; 
                if(isset($this->licenseDetails['paymentMode']) && $this->licenseDetails['paymentMode']!="CASH")
                {
                    $rules["licenseDetails.chequeNo"] ="required";
                    $rules["licenseDetails.chequeDate"] ="required|date|date_format:Y-m-d|after_or_equal:$mNowdate";
                    $rules["licenseDetails.bankName"] ="required|regex:$mRegex";
                    $rules["licenseDetails.branchName"] ="required|regex:$mRegex";
                } 
            }
            
        }
        elseif(in_array($mApplicationTypeId, [3])) # 3- Amendment
        {
            $rules["firmDetails.areaSqft"]="required|numeric";
            //$rules["firmDetails.businessAddress"]="required|regex:$mRegex";
            $rules["firmDetails.businessDescription"]="required|regex:$mRegex"; 
            // $rules["firmDetails.firmName"]="required|regex:$mFramNameRegex";
            $rules["firmDetails.holdingNo"]="required";
            $rules["initialBusinessDetails.ownershipType"]="required|int";            
            $rules["licenseDetails.licenseFor"]="required|int";    
            $rules["initialBusinessDetails.firmType"]="required|int";
            if(isset($this->initialBusinessDetails['firmType']) && $this->initialBusinessDetails['firmType']==5)
            {
                $rules["initialBusinessDetails.otherFirmType"]="required|regex:$mRegex";
            }       
            if($mApplicationTypeId!=4 && strtoupper($mUserType)!="ONLINE")
            {
                $rules["licenseDetails.totalCharge"] = "required|numeric";
            }
            if(in_array(strtoupper($mUserType),["JSK","UTC","TC","SUPER ADMIN","TL"]))
            {
                $rules["licenseDetails.paymentMode"]="required|alpha"; 
                if(isset($this->licenseDetails['paymentMode']) && $this->licenseDetails['paymentMode']!="CASH")
                {
                    $rules["licenseDetails.chequeNo"] ="required";
                    $rules["licenseDetails.chequeDate"] ="required|date|date_format:Y-m-d|after_or_equal:$mNowdate";
                    $rules["licenseDetails.bankName"] ="required|regex:$mRegex";
                    $rules["licenseDetails.branchName"] ="required|regex:$mRegex";
                } 
            }   
            $rules["ownerDetails"] = "array";
            if($this->ownerDetails)
            {
                $rules["ownerDetails.*.businessOwnerName"]="required|regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9]+)*$/";
                $rules["ownerDetails.*.guardianName"]="regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9]+)*$/";
                $rules["ownerDetails.*.mobileNo"]="required|digits:10|regex:/[0-9]{10}/";
                $rules["ownerDetails.*.email"]="email"; 
            }
        }
        return $rules;
    }
}
