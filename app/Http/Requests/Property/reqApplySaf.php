<?php

namespace App\Http\Requests\Property;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class reqApplySaf extends FormRequest
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
        $mNowDate     = Carbon::now()->format("Y-m-d");
        $mNowDateYm   = Carbon::now()->format("Y-m");
        if($this->getMethod()=="GET")
        {
            return[];
        }
        $rules['assessmentType'] = "required|int|in:1,2,3";
        if(isset($this->assessmentType) && $this->assessmentType ==3)
        {
            $rules['transferModeId'] = "required";
            $rules['dateOfPurchase'] = "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
            $rules["isOwnerChanged"] = "required|bool";
        }                
        $rules['ward']          = "required|digits_between:1,9223372036854775807";
        $rules['propertyType']  = "required|int";
        $rules['ownershipType'] = "required|int";
        $rules['roadType']      = "required|numeric";
        $rules['areaOfPlot']    = "required|numeric";
        $rules['isMobileTower'] = "required|bool";
        if(isset($this->isMobileTower) && $this->isMobileTower)
        {
            $rules['mobileTower.area'] = "required|numeric";
            $rules['mobileTower.dateFrom'] = "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
        }
        $rules['isHoardingBoard'] = "required|bool";
        if(isset($this->isHoardingBoard) && $this->isHoardingBoard)
        {
            $rules['hoardingBoard.area'] = "required|numeric";
            $rules['hoardingBoard.dateFrom'] = "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
        }
        $rules['isPetrolPump'] = "required|bool";
        if(isset($this->isPetrolPump) && $this->isPetrolPump)
        {
            $rules['petrolPump.area'] = "required|numeric";
            $rules['petrolPump.dateFrom'] = "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
        }
        if(isset($this->propertyType) && $this->propertyType==4)
        {
            $rules['landOccupationDate'] = "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
        }
        else
        {
            $rules['floor']        = "required|array";
            if(isset($this->floor) && $this->floor)
            {
                $rules["floor.*.floorNo"]           =   "required|int";
                $rules["floor.*.useType"]           =   "required|int";
                $rules["floor.*.constructionType"]  =   "required|int|in:1,2,3";
                $rules["floor.*.occupancyType"]     =   "required|int";

                $rules["floor.*.buildupArea"]       =   "required|numeric";
                $rules["floor.*.dateFrom"]          =   "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
                $rules["floor.*.dateUpto"]          =   "nullable|date|date_format:Y-m-d|before_or_equal:$mNowDate";
            }
        }
        $rules['isWaterHarvesting'] = "required|bool";
        if(isset($this->assessmentType) && $this->assessmentType !=1)
        {
            $rules['previousHoldingId'] = "required|digits_between:1,9223372036854775807";
            $rules['holdingNo']         = "required|string";
        }
        $rules['zone']           = "required|int|in:1,2";
        if(isset($this->assessmentType) && $this->assessmentType ==1 || ($this->assessmentType ==3 && $this->isOwnerChanged))
        {
            $rules['owner']        = "required|array";
            if(isset($this->owner) && $this->owner)
            {
                $rules["owner.*.ownerName"]           =   "required|regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9]+)*$/";
                $rules["owner.*.gender"]              =   "required|int|in:1,2,3";
                $rules["owner.*.dob"]                 =   "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
                $rules["owner.*.guardianName"]        =   "required|regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9]+)*$/";
                $rules["owner.*.relation"]            =   "required|string|in:S/O,W/O,D/O";
                $rules["owner.*.mobileNo"]            =   "required|digits:10|regex:/[0-9]{10}/";
                $rules["owner.*.email"]               =   "email|nullable";
                $rules["owner.*.pan"]                 =   "string|nullable";
                $rules["owner.*.aadhar"]              =   "digits:12|regex:/[0-9]{12}/|nullable";
                $rules["owner.*.isArmedForce"]        =   "required|bool";
                $rules["owner.*.isSpeciallyAbled"]    =   "required|bool";
            }
        }
        return $rules;
    }
}