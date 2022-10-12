<?php

namespace App\EloquentClass\Property;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

/**
 * | --------- Saf Calculation Class -----------------
 * | Created On - 12-10-2022 
 * | Created By - Anshu Kumar
 */
class SafCalculation
{
    /**
     * | Get Building Rule Set
     * | --------------------- Initialization ---------------------- | 
     * | @param floorDatefrom Floor Installation Date
     * | #todayDate > Current Date
     * | #virtualDate > Back 12 years Date from present
     * | #mainDate > The final Date after reverting back to 12 years
     */
    public function getRuleSet($floorDateFrom)
    {
        $todayDate = Carbon::now();
        $virtualDate = $todayDate->subYears(12)->format('Y-m-d');

        if ($floorDateFrom > $virtualDate) {
            $mainDate = $floorDateFrom;
        }
        if ($floorDateFrom < $virtualDate) {
            $mainDate = $virtualDate;
        }

        if ($mainDate < '2016-04-01') {
            return "RuleSet 1 + Current";
        }

        if ($mainDate >= '2016-04-01' && $mainDate <= '2022-03-31') {
            return "RuleSet2 and Current";
        }

        if ($mainDate >= '01-04-2022') {
            return 'Current RuleSet';
        }
    }

    /** 
     * | For Building
     * | ======================== getBuildingTax ========================= |
     *  
     * | ------------------ Initialization -------------- |
     * | #getFloors[] > getting all the floors in array
     * | #floorsInstallDate > Contains Floor Install Date in array
     * | #floorDateFrom > Installation Date for particular floor
     * | #refGetRuleSet > get the Rule Set by the current object method getRuleSet()
     * | 
     */
    public function getBuildingTax(Request $req)
    {
        try {
            $getFloors = $req['floor'];
            $floorsInstallDate = array();
            foreach ($getFloors as $getFloor) {
                $floorDateFrom = $getFloor['dateFrom'];
                $refGetRuleSet = $this->getRuleSet($floorDateFrom);
                array_push($floorsInstallDate, $refGetRuleSet);
            }
            return $floorsInstallDate;
        } catch (Exception $e) {
            return $e;
        }
    }
}
