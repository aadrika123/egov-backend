<?php

namespace App\Http\Requests\Trade;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class paymentCounter extends FormRequest
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
        $mRegex         = '/^[a-zA-Z1-9][a-zA-Z1-9\.\s]+$/';
        $mNowDate       = Carbon::now()->format('Y-m-d'); 
        $rules["paymentMode"]="required|alpha";
        $rules["licenceId"]="required|int"; 
        $rules["licenseFor"]="required|int";
        $rules["totalCharge"] = "required|numeric";               
        if(isset($this->paymentMode) && $this->paymentMode!="CASH")
        {
            $rules["chequeNo"] ="required";
            $rules["chequeDate"] ="required|date|date_format:Y-m-d|after_or_equal:$mNowDate";
            $rules["bankName"] ="required|regex:$mRegex";
            $rules["branchName"] ="required|regex:$mRegex";
        } 
        return $rules;
    }
}