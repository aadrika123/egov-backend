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
        $this->RainWaterHarvesting = $RainWaterHarvesting;
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
                'dateOfCompletion' => 'required|date',
                'propertyId' => 'required',
            ]
        );
        if ($validated->fails()) {
            return responseMsg(false, "validation error", $validated->errors(), 401);
        }
        return $this->RainWaterHarvesting->waterHarvestingApplication($request);
    }

    //inbox list
    public function harvestingInbox()
    {
        return $this->RainWaterHarvesting->harvestingInbox();
    }


    //outbox list
    public function harvestingOutbox()
    {
        return $this->RainWaterHarvesting->harvestingOutbox();
    }

    //post next level
    public function postNextLevel(Request $req)
    {
        $req->validate([
            'harvestingId' => 'required',
            'senderRoleId' => 'required',
            'receiverRoleId' => 'required',
            'comment' => 'required'
        ]);
        return $this->RainWaterHarvesting->postNextLevel($req);
    }

    //harvestig list
    public function waterHarvestingList()
    {
        return $this->RainWaterHarvesting->waterHarvestingList();
    }

    //harvesting list by id
    public function harvestingListById(Request $req)
    {
        return $this->RainWaterHarvesting->harvestingListById($req);
    }

    //harvesting doc by id
    public function harvestingDocList(Request $req)
    {
        return $this->RainWaterHarvesting->harvestingDocList($req);
    }

    //doc upload
    public function docUpload(Request $req)
    {
        return $this->RainWaterHarvesting->docUpload($req);
    }

    //doc status
    public function docStatus(Request $req)
    {
        return $this->RainWaterHarvesting->docStatus($req);
    }
}
