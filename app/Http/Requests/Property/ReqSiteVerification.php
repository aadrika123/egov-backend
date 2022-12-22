<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class ReqSiteVerification extends FormRequest
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
            'verificationStatus' => 'required|bool',
            'safId' => 'required|integer',
            'propertyType' => 'required|integer',
            'roadTypeId' => 'required|integer',
            'areaOfPlot' => 'required|numeric',
            'wardId' => 'required|integer',
            'isMobileTower' => 'required|bool',
            'mobileTowerArea' => 'required_if:isMobileTower,1',
            'mobileTowerDate' => 'required_if:isMobileTower,1',
            'isHoardingBoard' => 'required|bool',
            'hoardingArea' => 'required_if:isHoardingBoard,1',
            'hoardingDate' => 'required_if:isHoardingBoard,1',
            'isPetrolPump' => 'required|bool',
            'petrolPumpUndergroundArea' => 'required_if:isPetrolPump,1',
            'petrolPumpDate' => 'required_if:isPetrolPump,1',
            'isHarvesting' => 'required|bool',
            'zone' => 'required|integer',
            'userId' => 'required|integer',
            'currentRoleId' => 'required|integer',
            'floorDetails' => 'required|array',
            'floorDetails.*.floorId' => 'required|integer',
            'floorDetails.*.floorMstrId' => 'required|integer',
            'floorDetails.*.usageType' => 'required|integer',
            'floorDetails.*.constructionType' => 'required|integer',
            'floorDetails.*.occupancyType' => 'required|integer',
            'floorDetails.*.builtupArea' => 'required|numeric',
            'floorDetails.*.fromDate' => 'required|date'
        ];
    }
}
