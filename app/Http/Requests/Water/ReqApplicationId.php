<?php

namespace App\Http\Requests\Water;

use Illuminate\Foundation\Http\FormRequest;

class ReqApplicationId extends FormRequest
{
    public function rules()
    {
        return [
            'applicationId'    => "required|digits_between:1,9223372036854775807",
        ];
    }
}
