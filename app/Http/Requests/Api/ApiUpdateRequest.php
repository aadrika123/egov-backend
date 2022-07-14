<?php

namespace App\Http\Requests\Api;

use App\Traits\Validate\ValidateTrait;
use Illuminate\Foundation\Http\FormRequest;

class ApiUpdateRequest extends FormRequest
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
            'end_point' => ['required'],
            'Description' => ['required']
        ];
    }
}
