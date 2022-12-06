<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Repository\Property\Interfaces\iRainWaterHarvesting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RainWaterHarvestingController extends Controller
{
    // constructer creation
    protected $RainWaterHarvesting;
    public function __construct(iRainWaterHarvesting $RainWaterHarvesting)
    {
        $this->RainWaterHarvesting = $RainWaterHarvesting ;
    }

    # function for the getWardMasterData
    public function getWardMasterData(Request $request)
    {
        return $this->RainWaterHarvesting->getWardMasterData($request);
    }

     # function to save the application of harvesting
     public function waterHarvestingApplication(Request $request)
     {
        $validated = Validator::make(
            $request->all(),
            [
                'isWaterHarvestingBefore' => 'required',
                'wardNo' => 'required|integer',
                'mobileNo' => ['required', 'min:10', 'max:10'],
                'holdingNo' => 'required',
                'dateOfCompletion' => 'required|date',

            ]
        );
        if ($validated->fails()) {
            return responseMsg(false, "validation error", $validated->errors(), 401);
        }
         return $this->RainWaterHarvesting->waterHarvestingApplication($request);
     }
}
