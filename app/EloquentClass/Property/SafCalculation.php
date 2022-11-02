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
     * | For Building
     * | ======================== calculateTax(1) ========================= |
     *  
     * | ------------------ Initialization -------------- |
     * | @var refPropertyType the Property Type id
     * | @var collection collects all the Rulesets and others in an array
     * | @var getFloors[] > getting all the floors in array
     * | @var floorsInstallDate > Contains Floor Install Date in array
     * | @var floorDateFrom > Installation Date for particular floor
     * | @var refRuleSet > get the Rule Set by the current object method readRuleSet()
     * | 
     */
    public function calculateTax(Request $req)
    {
        try {
            $refPropertyType = $req->propertyType;
            $collection = [];
            // Means the Property Type is not a vacant Land
            if ($refPropertyType != 4) {
                $getFloors = $req['floor'];
                // Check If the one of the floors is commercial
                $readCommercial = collect($getFloors)->where('useType', '!=', 1);
                $isResidential = $readCommercial->isEmpty();

                foreach ($getFloors as $key => $getFloor) {
                    $floorDateFrom = $getFloor['dateFrom'];
                    $floorDateTo = $getFloor['dateUpto'];
                    $refQuaterlyRuleSets = $this->calculateQuaterlyRulesets($floorDateFrom, $floorDateTo, $getFloor, $key);
                    $collection['details'][$key] = $refQuaterlyRuleSets;
                }
            }
            return $collection;
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Calculate Quaterly Rulesets (1.1)
     */
    public function calculateQuaterlyRulesets($floorDateFrom, $floorDateTo, $getFloor, $key)
    {
        $ruleSet = [];
        $todayDate = Carbon::now();
        $virtualDate = $todayDate->subYears(12)->format('Y-m-d');

        if ($floorDateFrom > $virtualDate) {
            $floorDateFrom = $floorDateFrom;
        }
        if ($floorDateFrom < $virtualDate) {
            $floorDateFrom = $virtualDate;
        }

        $floorDetail =
            [
                'floor_no' => $getFloor['floorNo'],
                'use_type' => $getFloor['useType'],
                'constructionType' => $getFloor['constructionType'],
                'buildupArea' => $getFloor['buildupArea']
            ];
        $refRuleSet = $this->readRuleSet($floorDateFrom, $getFloor['dateUpto']);
        $ruleSet = $floorDetail;
        $ruleSet[$floorDateFrom] = $refRuleSet;
        return $ruleSet;
    }

    /**
     * | Get Building Rule Set
     * | --------------------- Initialization ---------------------- | 
     * | @param dateFrom Floor Installation Date
     * | #todayDate > Current Date
     * | #virtualDate > Back 12 years Date from present
     * | #mainDate > The final Date after reverting back to 12 years
     * | #checkFloorToNullable > Checks the condition of Floor Upto nullable
     */
    public function readRuleSet($dateFrom)
    {
        $ruleSet = [];
        $todayDate = Carbon::now();
        $virtualDate = $todayDate->subYears(12)->format('Y-m-d');

        if ($dateFrom > $virtualDate) {
            $mainDate = $dateFrom;
        }
        if ($dateFrom < $virtualDate) {
            $mainDate = $virtualDate;
        }
        $checkFloorToNullable = is_null($dateFrom) == true;

        // RuleSet1 Condition
        if ($dateFrom < '2016-04-01' && $dateFrom < '2016-04-01' && !$checkFloorToNullable) {
            $ruleSet = [
                'rule_set' => 'RuleSet1',
                'qtr' => '1',
                'due_date' => '20-09-2000'
            ];
            return $ruleSet;
        }

        // RuleSet1 and RuleSet2 Condition
        if ($dateFrom < '2016-04-01' && $dateFrom < '2022-04-01' && !$checkFloorToNullable) {
            return ["RuleSet1", "RuleSet2"];
        }

        // RuleSet 1 , RuleSet2 and RuleSet3 Condition
        if ($dateFrom < '2016-04-01' && $checkFloorToNullable) {
            return ["RuleSet1", "RuleSet2", "RuleSet3"];
        }

        // RuleSet 1, Ruleset2 and Ruleset3 Condition 2
        if ($dateFrom < '2016-04-01' && $dateFrom >= '2022-04-01') {
            return ["RuleSet1", "RuleSet2", "RuleSet3"];
        }


        // RuleSet2 Condition
        if ($dateFrom >= '2016-04-01' && $dateFrom <= '2022-03-31' && $dateFrom < '2022-04-01' && $dateFrom >= '2016-04-01') {
            return ["RuleSet2"];
        }

        // RuleSet 2 and RuleSet3 Condition
        if ($dateFrom >= '2016-04-01' && $dateFrom < '2022-04-01' && $checkFloorToNullable) {
            return ["RuleSet2", "RuleSet3"];
        }

        // RuleSet 2 and RuleSet3 Condition 2
        if ($dateFrom >= '2016-04-01' && $dateFrom <= '2022-03-31' && $dateFrom >= '2022-04-01') {
            return ["RuleSet2", "RuleSet3"];
        }

        // RuleSet 3 Condition
        if ($dateFrom >= '2022-04-01' && $checkFloorToNullable) {
            return ["RuleSet3"];
        }

        // RuleSet 3 Condition 2
        if ($dateFrom >= '2022-04-01' && $dateFrom >= '2022-04-01') {
            return ["RuleSet3"];
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
