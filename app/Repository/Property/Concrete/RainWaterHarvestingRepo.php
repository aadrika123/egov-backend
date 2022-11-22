<?php

namespace App\Repository\Property\Concrete;

use App\Models\Property\PropRainWaterHarvesting;
use App\Models\Water\WaterApplicant;
use App\Repository\Property\Interfaces\iRainWaterHarvesting;
use App\Traits\Property\SAF;
use App\Traits\Ward;
use App\Traits\Workflow\Workflow;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * | Created On - 22-11-2022
 * | Created By - Sam Kerketta
 * | Property RainWaterHarvesting apply
 */

class RainWaterHarvestingRepo implements iRainWaterHarvesting
{
    use SAF;
    use Workflow;
    use Ward;

    /**
     * |----------------------- getWardMasterData --------------------------
     * |  Query cost => 400-438 ms 
     * |@param request
     * |@var ulbId
     * |@var wardList
     */
    public function getWardMasterData($request)
    {
        try {
            $ulbId = auth()->user()->ulb_id;
            $wardList = $this->getAllWard($ulbId);
            return responseMsg(true, "List of wards", $wardList);
        } catch (Exception $error) {
            return responseMsg(false, "Error!", $error->getMessage());
        }
    }


    /**
     * |----------------------- postWaterHarvestingApplication --------------------------
     * |  Query cost => 350 - 490 ms 
     * |@param request
     * |@var ulbId
     * |@var wardList
     */
    public function waterHarvestingApplication($request)
    {
        try {
            $userId = auth()->user()->id;
            $ulbId = auth()->user()->ulb_id;
            $waterHaravesting = new PropRainWaterHarvesting();
            return  $this->waterApplicationSave($waterHaravesting, $request, $ulbId, $userId);
        } catch (Exception $error) {
            return responseMsg(false, "Error!", $error->getMessage());
        }
    }


    // function to save the application for the waterharvesting
    public function waterApplicationSave($waterHaravesting, $request, $ulbId, $userId)
    {
        try {

            $waterHaravesting->harvesting_status = $request->isWaterHarvestingBefore;
            $waterHaravesting->name  =  $request->name;
            $waterHaravesting->guardian_name  =  $request->guardianName;
            $waterHaravesting->ward_id  =  $request->wardNo;
            $waterHaravesting->mobile_no  =  $request->mobileNo;
            $waterHaravesting->holding_no  =  $request->holdingNo;
            $waterHaravesting->building_address  =  $request->buildingAddress;
            $waterHaravesting->date_of_completion  =  $request->dateOfCompletion;
            $waterHaravesting->user_id = $userId;
            $waterHaravesting->ulb_id = $ulbId;
            $waterHaravesting->save();

            return responseMsg(true, "Application applied!", "");
        } catch (Exception $error) {
            return responseMsg(false, "Data not saved", $error->getMessage());
        }
    }
}
