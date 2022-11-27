<?php

namespace App\Repository\Property\Concrete;

use App\Models\Property\PropActiveHarvesting;
use App\Repository\Property\Interfaces\iRainWaterHarvesting;
use App\Traits\Property\SAF;
use App\Traits\Ward;
use App\Traits\Workflow\Workflow;
use Exception;

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
     * |----------------------- postWaterHarvestingApplication 1 --------------------------
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
            $waterHaravesting = new PropActiveHarvesting();
            return  $this->waterApplicationSave($waterHaravesting, $request, $ulbId, $userId);
        } catch (Exception $error) {
            return responseMsg(false, "Error!", $error->getMessage());
        }
    }


    /**
     * |----------------------- function for the savindg the application details 1.1 --------------------------
     * |@param waterHaravesting
     * |@param request
     * |@param ulbId
     * |@param userId
     * |@var applicationNo
     */
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

            $applicationNo = $this->generateApplicationNo($ulbId, $userId);
            $waterHaravesting->application_no = $applicationNo;
            $waterHaravesting->save();

            return responseMsg(true, "Application applied!", $applicationNo);
        } catch (Exception $error) {
            return responseMsg(false, "Data not saved", $error->getMessage());
        }
    }

    /**
     * |----------------------- function for generating application no 1.1.1 --------------------------
     * |@param ulbId
     * |@param userId
     * |@var applicationId
     */
    public function generateApplicationNo($ulbId, $userId)
    {
        $applicationId = "RWH/" . $ulbId . $userId . rand(0, 99999999999999);
        return $applicationId;
    }
}
