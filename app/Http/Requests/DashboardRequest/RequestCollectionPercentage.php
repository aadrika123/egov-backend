<?php

namespace App\Http\Requests\DashboardRequest;

use App\Http\Requests\AllRequest;
use Illuminate\Foundation\Http\FormRequest;

class RequestCollectionPercentage extends AllRequest
{
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $rules['month'] = "nullable|in:1,2,3,4,5,6,7,8,9,10,11,12";
        $rules['year'] = "nullable|regex:/^\d{4}-\d{4}$/";
        return $rules;
    }
}
