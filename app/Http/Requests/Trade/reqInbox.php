<?php

namespace App\Http\Requests\Trade;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
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
    protected function failedValidation(Validator $validator)
    { 
        throw new HttpResponseException(
            response()->json(
                [
                    'status' => false,
                    'message' => 'The given data was invalid',
                    'errors' => $validator->errors()
                ], 
                422)
        );
        
    }
}
