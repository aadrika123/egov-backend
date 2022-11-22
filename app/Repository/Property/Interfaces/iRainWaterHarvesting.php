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
}
