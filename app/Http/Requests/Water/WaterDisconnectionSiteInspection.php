<?php

namespace App\Http\Requests\Water;

use App\Models\water\WaterDisconnectionSiteInspections;
use Illuminate\Foundation\Http\FormRequest;

class WaterDisconnectionSiteInspection extends siteAdjustment
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = parent::rules();
        $ModelWaterDisconnectionSiteInspections = new WaterDisconnectionSiteInspections();
        $rules ["applicationId"]="required|digits_between:1,9223372036854775807|exists:".$ModelWaterDisconnectionSiteInspections->getConnectionName().".".$ModelWaterDisconnectionSiteInspections->getTable().",id";
        return $rules;
    }
}
