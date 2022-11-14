<?php

namespace App\Http\Requests\Trade;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;

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
        $applicationTypeId = Config::get("TradeConstant.APPLICATION-TYPE.".$this->applicationType);
        dd($applicationTypeId);
        $rules = [];

        return $rules;
    }
}
