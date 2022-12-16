<?php

namespace App\Repository\Property\Interfaces;

/**
 * | Created On - 22-11-2022 
 * | Created By - Sam kerketta
 * | Created For - The Interface for RainWaterHarvesting / Property
 */
interface iRainWaterHarvesting
{
    public function getWardMasterData($request);
    public function waterHarvestingApplication($request);

    public function harvestingInbox();
    public function harvestingOutbox();
    public function postNextLevel($req);
    public function waterHarvestingList();
    public function harvestingListById($req);
    public function harvestingDocList($req);
    public function docUpload($req);
    public function docStatus($req);
}
