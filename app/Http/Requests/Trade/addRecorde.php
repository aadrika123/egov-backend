<?php

namespace App\Http\Requests\Trade;

use App\Repository\Trade\Trade;
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
        $mapplicationTypeId = Config::get("TradeConstant.APPLICATION-TYPE.".$this->applicationType);
        $mnowdate = Carbon::now()->format('Y-m-d'); 
        $mtimstamp = Carbon::now()->format('Y-m-d H:i:s');                
        $mregex = '/^[a-zA-Z1-9][a-zA-Z1-9\.\s]+$/';
        $malphaNumCommaSlash='/^[a-zA-Z0-9- ]+$/i';
        $malphaSpace ='/^[a-zA-Z ]+$/i';
        $malphaNumhyphen ='/^[a-zA-Z0-9- ]+$/i';
        $mnumDot = '/^\d+(?:\.\d+)+$/i';
        $mdateFormatYYYMMDD ='/^([12]\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))+$/i';
        $mdateFormatYYYMM='/^([12]\d{3}-(0[1-9]|1[0-2]))+$/i';
        $reftrade = new Trade();
        $muserType = $reftrade->applyFrom();
        $rules = [];
        if(in_array($mapplicationTypeId, [1]))
        {

            $rules["firmDetails.areaSqft"]="required|numeric";
            $rules["firmDetails.businessAddress"]="required|regex:$mregex";
            $rules["firmDetails.businessDescription"]="required|regex:$mregex"; 
            $rules["firmDetails.firmEstdDate"]="required|date"; 
            $rules["firmDetails.firmName"]="required|regex:$mregex";
            if (in_array($mapplicationTypeId, ["2"])) 
            {                    
                $rules["firmDetails.holdingNo"]="required|regex:$mregex";
            } 
            $rules["firmDetails.premisesOwner"]="required|regex:$mregex";
            $rules["firmDetails.natureOfBusiness"]="required|array";
            $rules["firmDetails.natureOfBusiness.*.id"]="required|int";
            $rules["firmDetails.newWardNo"]="required|int";
            $rules["firmDetails.wardNo"]="required|int";
            $rules["firmDetails.tocStatus"] = "required|bool";
            $rules["firmDetails.landmark"]="regex:$mregex";
            $rules["firmDetails.categoryTypeId"]="int";
            $rules["firmDetails.k_no"] = "digits|regex:/[0-9]{10}/";
            $rules["firmDetails.bind_book_no"] = "regex:$mregex";
            $rules["firmDetails.account_no"] = "regex:$mregex";
            if(strtoupper($muserType)=="ONLINE")
            {
                $rules["firmDetails.pincode"]="digits:6|regex:/[0-9]{6}/";                    
            }               
            
            $rules["initialBusinessDetails.applyWith"]="required|int";
            $rules["initialBusinessDetails.firmType"]="required|int";
            if(isset($this->initialBusinessDetails['firmType']) && $this->initialBusinessDetails['firmType']==5)
            {
                $rules["initialBusinessDetails.otherFirmType"]="required|regex:$mregex";
            }
            $rules["initialBusinessDetails.ownershipType"]="required|int";
            if( isset($this->initialBusinessDetails['applyWith']) && $this->initialBusinessDetails['applyWith']==1)
            {
                $rules["initialBusinessDetails.noticeNo"]="required";
                $rules["initialBusinessDetails.noticeDate"]="required|date";  
            }
            $rules["licenseDetails.licenseFor"]="required|int";
            if($mapplicationTypeId!=4 && strtoupper($muserType)!="ONLINE")
            {
                $rules["licenseDetails.totalCharge"] = "required|numeric";
            }
            if(isset($this->firmDetails["tocStatus"]) && $this->firmDetails["tocStatus"])
            {
                $rules["licenseDetails.licenseFor"]="required|int|max:1";
            }
            if(in_array(strtoupper($muserType),["JSK","UTC","TC","SUPER ADMIN","TL"]))
            {
                $rules["licenseDetails.paymentMode"]="required|alpha"; 
                if(isset($this->licenseDetails['paymentMode']) && $this->licenseDetails['paymentMode']!="CASH")
                {
                    $rules["licenseDetails.chequeNo"] ="required";
                    $rules["licenseDetails.chequeDate"] ="required|date|date_format:Y-m-d|after_or_equal:$mnowdate";
                    $rules["licenseDetails.bankName"] ="required|regex:$mregex";
                    $rules["licenseDetails.branchName"] ="required|regex:$mregex";
                } 
            }

            $rules["ownerDetails"] = "required|array";
            $rules["ownerDetails.*.businessOwnerName"]="required|regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9]+)*$/";
            $rules["ownerDetails.*.guardianName"]="regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9]+)*$/";
            $rules["ownerDetails.*.mobileNo"]="required|digits:10|regex:/[0-9]{10}/";
            $rules["ownerDetails.*.email"]="email";
            
            
        }
        elseif(in_array($mapplicationTypeId, [2,4])) # 2- Renewal ,4- Surender
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
            if($mapplicationTypeId!=4 && strtoupper($muserType)!="ONLINE")
            {
                $rules["licenseDetails.totalCharge"] = "required|numeric";
            }
            if(in_array(strtoupper($muserType),["JSK","UTC","TC","SUPER ADMIN","TL"]) && $mapplicationTypeId==2)
            {
                $rules["licenseDetails.paymentMode"]="required|alpha"; 
                if(isset($this->licenseDetails['paymentMode']) && $this->licenseDetails['paymentMode']!="CASH")
                {
                    $rules["licenseDetails.chequeNo"] ="required";
                    $rules["licenseDetails.chequeDate"] ="required|date|date_format:Y-m-d|after_or_equal:$mnowdate";
                    $rules["licenseDetails.bankName"] ="required|regex:$mregex";
                    $rules["licenseDetails.branchName"] ="required|regex:$mregex";
                } 
            }
            
        }
        elseif(in_array($mapplicationTypeId, [3])) # 3- Amendment
        {
            $rules["firmDetails.areaSqft"]="required|numeric";
            $rules["firmDetails.businessAddress"]="required|regex:$mregex";
            $rules["firmDetails.businessDescription"]="required|regex:$mregex"; 
            $rules["firmDetails.firmEstdDate"]="required|date"; 
            $rules["firmDetails.firmName"]="required|regex:$mregex";
            $rules["firmDetails.holdingNo"]="required";
            $rules["firmDetails.premisesOwner"]="required|regex:$mregex";
            $rules["firmDetails.natureOfBusiness"]="required|array";
            $rules["firmDetails.natureOfBusiness.*.id"]="required|int";
            $rules["firmDetails.newWardNo"]="required|int";
            $rules["firmDetails.wardNo"]="required|int";
            $rules["firmDetails.tocStatus"] = "required|bool";
            $rules["firmDetails.landmark"]="regex:$mregex";
            $rules["firmDetails.categoryTypeId"]="int";
            $rules["firmDetails.k_no"] = "digits|regex:/[0-9]{10}/";
            $rules["firmDetails.bind_book_no"] = "regex:$mregex";
            $rules["firmDetails.account_no"] = "regex:$mregex";
            if(strtoupper($muserType)=="ONLINE")
            {
                $rules["firmDetails.pincode"]="digits:6|regex:/[0-9]{6}/";                    
            } 
            $rules["initialBusinessDetails.ownershipType"]="required|int";
            if( isset($this->initialBusinessDetails['applyWith']) && $this->initialBusinessDetails['applyWith']==1)
            {
                $rules["initialBusinessDetails.noticeNo"]="required";
                $rules["initialBusinessDetails.noticeDate"]="required|date";  
            }
            $rules["licenseDetails.licenseFor"]="required|int";
            if(isset($this->firmDetails["tocStatus"]) && $this->firmDetails["tocStatus"])
            {
                $rules["licenseDetails.licenseFor"]="required|int|max:1";
            }
            if($mapplicationTypeId!=4 && strtoupper($muserType)!="ONLINE")
            {
                $rules["licenseDetails.totalCharge"] = "required|numeric";
            }
            if(isset($this->firmDetails["tocStatus"]) && $this->firmDetails["tocStatus"])
            {
                $rules["licenseDetails.licenseFor"]="required|int|max:1";
            }
            if(in_array(strtoupper($muserType),["JSK","UTC","TC","SUPER ADMIN","TL"]))
            {
                $rules["licenseDetails.paymentMode"]="required|alpha"; 
                if(isset($this->licenseDetails['paymentMode']) && $this->licenseDetails['paymentMode']!="CASH")
                {
                    $rules["licenseDetails.chequeNo"] ="required";
                    $rules["licenseDetails.chequeDate"] ="required|date|date_format:Y-m-d|after_or_equal:$mnowdate";
                    $rules["licenseDetails.bankName"] ="required|regex:$mregex";
                    $rules["licenseDetails.branchName"] ="required|regex:$mregex";
                } 
            }    
        }
        dd($rules);
        return $rules;
    }
}
