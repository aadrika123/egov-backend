<?php

namespace App\Http\Requests\Trade;

use Illuminate\Foundation\Http\FormRequest;

class reqInbox extends FormRequest
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
        return [
            "key"       =>  "string",
            "wardNo"    =>  "digits_between:1,9223372036854775807",
            "formDate"  =>  "date",
            "toDate"    =>  "date",
        ];
    }
}
