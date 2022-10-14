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
 * | Escaping for a while of Calculation part from 13-10-2022
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
    public function getRuleSet($floorDateFrom, $floorDateTo = null)
    {
        $todayDate = Carbon::now();
        $virtualDate = $todayDate->subYears(12)->format('Y-m-d');

        if ($floorDateFrom > $virtualDate) {
            $mainDate = $floorDateFrom;
        }
        if ($floorDateFrom < $virtualDate) {
            $mainDate = $virtualDate;
        }

        // RuleSet 1 , RuleSet2 and RuleSet3 Condition
        if ($mainDate < '2016-04-01' && $floorDateTo == null) {
            return ["RuleSet1", "RuleSet2", "RuleSet3"];
        }
        // RuleSet 2 and RuleSet3 Condition
        if ($mainDate >= '2016-04-01' && $mainDate <= '2022-03-31' && $floorDateTo == null || $floorDateTo >= '2016-04-01') {
            return ["RuleSet2", "RuleSet3"];
        }
        // RuleSet 3 Condition
        if ($mainDate >= '01-04-2022' && $floorDateTo == null) {
            return ["RuleSet3"];
        }

        // RuleSet1 Condition
        if ($mainDate < '2016-04-01' && $floorDateTo < '2016-04-01') {
            return ["RuleSet1"];
        }

        // RuleSet2 Condition
        if ($mainDate >= '2016-04-01' && $floorDateTo < '2022-04-01') {
            return ["RuleSet2"];
        }

        // RuleSet1 and RuleSet2 Condition
        if ($mainDate <= '2022-04-01' && $floorDateTo < '2022-04-01') {
            return ["RuleSet1", "RuleSet2"];
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
                $floorDateTo = $getFloor['dateUpto'];
                $refGetRuleSet = $this->getRuleSet($floorDateFrom, $floorDateTo);
                array_push($floorsInstallDate, $refGetRuleSet);
            }
            $ruleSet = collect($floorsInstallDate);
            $collection = $ruleSet->map(function ($item, $key) {
                return $item;
            });
            return $collection;
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * | RuleSet1
     */
    public function ruleSet1()
    {
    }

    /**
     * | RuleSet2
     */
    public function ruleSet2()
    {
    }

    /**
     * | RuleSet3
     */
    public function ruleSet3()
    {
    }
}
