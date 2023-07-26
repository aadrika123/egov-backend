<?php

namespace App\Http\Requests\Water;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Config;

class reqMeterEntry extends FormRequest
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
        $refMeterConnectionType = Config::get('waterConstaint.WATER_MASTER_DATA.METER_CONNECTION_TYPE');
        $rules['connectionType']        = 'required|int:1,2,3,4';
        $rules['consumerId']            = "required|digits_between:1,9223372036854775807";
        $rules['connectionDate']        = 'required|date|date_format:Y-m-d';
        $rules['oldMeterFinalReading']  = 'required';

        if (isset($this->connectionType) && $this->connectionType && in_array($this->connectionType, [$refMeterConnectionType['Meter'], $refMeterConnectionType['Gallon']])) {
            $rules['meterNo']                   = 'required';
            $rules['document']                  = 'required|mimes:pdf,jpeg,jpg,png|max:2048';
            $rules['newMeterInitialReading']    = 'required';
        }
        if (isset($this->connectionType) && $this->connectionType && $this->connectionType == $refMeterConnectionType['Fixed']) {
            $rules['newMeterInitialReading'] = 'required';
        }
        if (isset($this->connectionType) && $this->connectionType && $this->connectionType == $refMeterConnectionType['Meter/Fixed']) {
            $rules['meterNo']               = 'required';
            $rules['document']              = 'required|mimes:pdf,jpeg,jpg,png|max:2048';
        }
        return $rules;
    }


    // Validation Error Message
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json(
                [
                    'status'   => false,
                    'message'  => 'The given data was invalid',
                    'errors'   => $validator->errors()
                ],
                422
            )
        );
    }
}
