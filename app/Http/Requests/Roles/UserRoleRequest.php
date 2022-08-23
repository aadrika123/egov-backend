<?php

namespace App\Http\Requests\Roles;

use App\Traits\Validate\ValidateTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Http\FormRequest;

class UserRoleRequest extends FormRequest
{
    use ValidateTrait;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->a();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'userID' => ['required', 'int']
        ];
    }

    /**
     * Create a response object from the given validation exception.
     *
     * @param  \Illuminate\Contracts\Validation\Validator;
     * @param  \Illuminate\Contracts\Validation\Validator;  $validator
     * @return Illuminate\Http\Exceptions\HttpResponseException
     */


    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
