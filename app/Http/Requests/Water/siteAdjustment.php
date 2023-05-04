<?php

namespace App\Http\Requests\Water;

use Illuminate\Foundation\Http\FormRequest;

class siteAdjustment extends FormRequest
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
        $rules['areaSqft'] = 'required|';
        $rules['propertyTypeId'] = 'required|int:1,2,3,4,5,6,7,8';
        $rules['connectionTypeId'] = 'required|int|in:1,2';
        $rules['latitude'] = 'required';
        $rules['longitude'] = 'required';
        return $rules;
    }
}
