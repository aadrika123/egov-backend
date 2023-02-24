<?php

namespace App\Http\Requests\Water;

use Illuminate\Foundation\Http\FormRequest;

class reqDeactivate extends FormRequest
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
        $rules['consumerId'] = 'required';
        $rules['remarks'] = 'required';
        $rules['applicationImage'] = 'required';
        $rules['deactivateReason'] = 'required|in:Double Connection,Waiver Committee,No Connection';
        return $rules;
    }
}
