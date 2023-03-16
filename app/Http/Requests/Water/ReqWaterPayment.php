<?php

namespace App\Http\Requests\Water;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;

class ReqWaterPayment extends FormRequest
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
        $rules = array();
        $offlinePaymentModes = Config::get('payment-constants.PAYMENT_OFFLINE_MODE_WATER');
        $cash = Config::get('payment-constants.PAYMENT_MODE.3');
        $rules['isInstallment'] = 'required|';
        if (isset($this['isInstallment']) && $this['isInstallment'] == "yes") {
            $rules['penaltyIds'] = "required|array";
        }
        if (isset($this['paymentMode']) &&  in_array($this['paymentMode'], $offlinePaymentModes) && $this['paymentMode'] != $cash) {
            $rules['chequeDate'] = "required|date|date_format:Y-m-d";
            $rules['bankName'] = "required";
            $rules['branchName'] = "required";
            $rules['chequeNo'] = "required";
        }
        $rules['chargeCategory'] = 'required';
        $rules['applicationId'] = 'required';
        $rules['penaltyAmount'] = 'required';
        return $rules;
    }
}
