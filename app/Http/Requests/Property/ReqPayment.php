<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;

class ReqPayment extends FormRequest
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
        $offlinePaymentModes = Config::get('payment-constants.PAYMENT_MODE_OFFLINE');
        $cash = Config::get('payment-constants.PAYMENT_MODE.3');
        if (isset($this['paymentMode']) &&  in_array($this['paymentMode'], $offlinePaymentModes) && $this['paymentMode'] != $cash) {
            $rules['chequeDate'] = "required|date|date_format:Y-m-d";
            $rules['bankName'] = "required";
            $rules['branchName'] = "required";
            $rules['chequeNo'] = "required";
        }
        $rules['ulbId'] = "required";

        return $rules;
    }
}