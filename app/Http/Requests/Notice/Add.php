<?php

namespace App\Http\Requests\Notice;

class Add extends Notice
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    
    public function rules()
    {
        $modul = "SAF,PROPERTY,TRADE LICENSE,WATER CONNECTION,WATER CONSUMER,ADVERTISMENT,MARKET,SOLID WASTE";
        $mRegex = '/^[a-zA-Z1-9][a-zA-Z1-9\. \s]+$/';
        $mFramNameRegex = '/^[a-zA-Z1-9][a-zA-Z1-9\.\,\'&\s]+$/';
        $rules = [
            "noticeType" => "required|in:1,2,3,4",
            "applicationId" => "nullable|digits_between:1,9223372036854775807",
            "moduleId"      => "nullable|digits_between:1,6",
            "moduleType"    => "nullable|in:$modul",
            "firmName"      => "nullable|regex:$mFramNameRegex",
            "ptnNo"         => "nullable",
            "holdingNo"     =>"nullable",
            "licenseNo"     => "nullable",
            "servedTo"      => "nullable",
            "address"       => "required|regex:$mFramNameRegex",
            "locality"      => "nullable|regex:$mFramNameRegex",
            "mobileNo"      => "required|digits:10|regex:/[0-9]{10}/",
            "noticeDescription" => "required|regex:$mFramNameRegex|min:50",
            "ownerName"     => "nullable|regex:$mRegex",
            "document"     => "required|mimes:pdf,jpg,jpeg,png|max:2048",
        ];
        return $rules;
    }
}
