<?php

namespace App\EloquentClass\Property;

use App\Models\Property\PropMRentalValue;
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
     * | For Building
     * | ======================== calculateTax (1) as base function ========================= |
     *  
     * | ------------------ Initialization -------------- |
     * | @var refPropertyType the Property Type id
     * | @var collection collects all the Rulesets and others in an array
     * | @var refFloors[] > getting all the floors in array
     * | @var floorsInstallDate > Contains Floor Install Date in array
     * | @var floorDateFrom > Installation Date for particular floor
     * | @var refRuleSet > get the Rule Set by the current object method readRuleSet()
     * | Query Run Time - 5
     */
    public function calculateTax(Request $req)
    {
        try {
            $refPropertyDetails = $req->all();
            $refPropertyType = $req->propertyType;
            $collection = [];
            // Means the Property Type is not a vacant Land
            if ($refPropertyType != 4) {
                $refFloors = $req['floor'];
                // Check If the one of the floors is commercial
                $readCommercial = collect($refFloors)->where('useType', '!=', 1);
                $isResidential = $readCommercial->isEmpty();

                foreach ($refFloors as $key => $refFloor) {
                    $floorDateFrom = $refFloor['dateFrom'];
                    $floorDateTo = $refFloor['dateUpto'];
                    $refQuaterlyRuleSets = $this->calculateQuaterlyRulesets($floorDateFrom, $floorDateTo, $refPropertyDetails, $key);
                    $collection['details'][$key] = $refQuaterlyRuleSets;
                }
            }
            return responseMsg(true, "Data Fetched", remove_null($collection));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Calculate Quaterly Rulesets (1.1)
     * | @param dateFrom The Date Starting from
     * | @param dateUptoTo the Date till the floor existed or property
     * | @param propertyDetails all the property Details
     * | @param key the key of the array from function 1 to distribute by floor details
     * ----------------------------------------------------------------
     * | @var array ruleSet contains all the QuaterlyRuleSets in array
     * | @var virtualDate is the 12 Years back date from the today's Date
     * | @var floorDetail all the floor details in case of Property Building Structured
     * | @var carbonDateFrom the Date from of the Property floor Details in Carbon format
     * | @var carbonDateUpto the Date Upto of the propety floor details In Carbon format
     * | @var collectRuleSets is the Collection of all the arrayRuleSets in laravel Collection
     * | @var uniqueRuleSets make our collection unique by due date and quater
     * | Query Run Time - 4
     * 
     */
    public function calculateQuaterlyRulesets($dateFrom, $dateUpTo, $propertyDetails, $key)
    {
        $ruleSet = [];
        $arrayRuleSet = [];
        $todayDate = Carbon::now();
        $virtualDate = $todayDate->subYears(12)->format('Y-m-d');
        $carbonDateFrom = Carbon::parse($dateFrom)->format('Y-m-d');
        $carbonDateUpto = Carbon::parse($dateUpTo)->format('Y-m-d');

        if ($dateUpTo == null) {
            $dateUpTo = Carbon::now()->format('Y-m-d');
        }

        if ($dateFrom > $virtualDate) {
            $dateFrom = $dateFrom;
        }
        if ($dateFrom < $virtualDate) {
            $dateFrom = $virtualDate;
        }
        // Floor Details
        $floorDetail =
            [
                'floor_no' => $propertyDetails['floor'][$key]['floorNo'],
                'use_type' => $propertyDetails['floor'][$key]['useType'],
                'constructionType' => $propertyDetails['floor'][$key]['constructionType'],
                'buildupArea' => $propertyDetails['floor'][$key]['buildupArea'],
                'dateFrom' => $propertyDetails['floor'][$key]['dateFrom'],
                'dateTo' => $propertyDetails['floor'][$key]['dateUpto']
            ];

        // Itteration for the RuleSets dateFrom wise 
        while ($carbonDateFrom <= $carbonDateUpto) {
            $refRuleSet = $this->readRuleSet($carbonDateFrom, $propertyDetails, $key);
            $carbonDateFrom = Carbon::parse($carbonDateFrom)->addMonth()->format('Y-m-d');              // CarbonDateFrom = CarbonDateFrom + 1 (add one months)
            array_push($arrayRuleSet, $refRuleSet[0]);
        }

        $collectRuleSets = collect($arrayRuleSet);
        $uniqueRuleSets = $collectRuleSets->unique('dueDate');

        $ruleSet = $floorDetail;
        $ruleSet['RuleSets'] = $uniqueRuleSets->values();

        return $ruleSet;
    }

    /**
     * | Get Rule Set (1.1.1)
     * | --------------------- Initialization ---------------------- | 
     * | @param dateFrom Installation Date of floor or Property
     * | @var ruleSets contains the ruleSet in an array
     * | Query Run Time - 3
     */
    public function readRuleSet($dateFrom, $propertyDetails, $key)
    {
        $ruleSets = [];
        // is implimented rule set 1 (before 2016-2017), (2016-2017 TO 2021-2022), (2021-2022 TO TILL NOW)
        if ($dateFrom < "2016-04-01") {
            $ruleSets[] = [
                "quarterYear" => calculateFyear($dateFrom),                              // Calculate Financial Year means to Calculate the FinancialYear
                "ruleSet" => "RuleSet1",
                "qtr" => calculateQtr($dateFrom),                                        // Calculate Quarter from the date
                "dueDate" => calculateQuaterDueDate($dateFrom),        // Calculate Quarter Due Date of the Date
                "Tax" => $this->calculateRuleSet1($propertyDetails, $key)
            ];
            return $ruleSets;
        }
        // is implimented rule set 2 (2016-2017 TO 2021-2022), (2021-2022 TO TILL NOW)
        if ($dateFrom < "2022-04-01") {
            $ruleSets[] = [
                "quarterYear" => calculateFyear($dateFrom),
                "ruleSet" => "RuleSet2",
                "qtr" => calculateQtr($dateFrom),
                "dueDate" => calculateQuaterDueDate($dateFrom)
            ];
            return $ruleSets;
        }

        // is implimented rule set 3 (2021-2022 TO TILL NOW)
        if ($dateFrom >= "2022-04-01") {
            $ruleSets[] = [
                "quarterYear" => calculateFyear($dateFrom),
                "ruleSet" => "RuleSet3",
                "qtr" => calculateQtr($dateFrom),
                "dueDate" => calculateQuaterDueDate($dateFrom)
            ];
            return $ruleSets;
        }
    }

    /**
     * | Calculation of Property Tax By RuleSet 1
     * ------------------------------------------------------------------
     * | @param propertyDetails request input of all the property details
     * | @param key keyvalue of the array of The Floor
     * ------------------ Initialization --------------------------------
     * | @var refBuildupArea buildup area for the floor 
     * | @var refZone Property Zone Type
     * | @var refFloorInstallationDate Floor's Installation Date
     * | @var refUsageType floor Usage Type ID
     * | @var refConstuctionType floor Construction Type ID
     * | @var refUlbId logged In user ulbId
     * | @var refOccupancyType floorOccupancy Type
     * | @var refPropertyType Property type 
     * | @var refRentalValue the rental value for the floor
     * | @var tempArv the temporary arv value for the calculation of Actual ARV
     * | @var arvCalcPercFactor the percentage factor to determine the ARV
     * | @var arv the quaterly ARV
     * ------------------ Calculation -----------------------------------
     * | $arv = ($tempArv * $arvCalPerFactor)/100;
     * | $latrineTax = ($arv * 7.5) / 100;
     * | $waterTax = ($arv * 7.5) / 100;
     * | $healthTax = ($arv * 6.25) / 100;
     * | $educationTax = ($arv * 5.0) / 100;
     * | $rwhPenalty = 0;
     * | $totalTax = $holdingTax + $latrineTax + $waterTax + $healthTax + $educationTax + $rwhPenalty;
     * | @return Tax totalTax/4 (Quaterly)
     * | Query RunTime=1
     */
    public function calculateRuleSet1($propertyDetails, $key)
    {
        $refBuildupArea = $propertyDetails['floor'][$key]['buildupArea'];
        $refZone = $propertyDetails['zone'];
        $refFloorInstallationDate = $propertyDetails['floor'][$key]['dateFrom'];
        $refUsageType = $propertyDetails['floor'][$key]['useType'];
        $refConstructionType = $propertyDetails['floor'][$key]['constructionType'];
        $refUlbId = auth()->user()->ulb_id;
        $refOccupancyType = $propertyDetails['floor'][$key]['occupancyType'];
        $refPropertyType = $propertyDetails['propertyType'];

        $refRentalValue = PropMRentalValue::select('rate')
            ->where('usage_types_id', $refUsageType)
            ->where('zone_id', $refZone)
            ->where('construction_types_id', $refConstructionType)
            ->where('ulb_id', $refUlbId)
            ->where('status', 1)
            ->first();

        if (!$refRentalValue) {
            return responseMsg(false, "Rental Value Not Available", "");
        }
        $tempArv = $refBuildupArea * (float)$refRentalValue->rate;
        $arvCalcPercFactor = 0;

        if ($refUsageType == 1 && $refOccupancyType == 2) {
            $arvCalcPercFactor += 30;
            // Condition if the property is Independent Building and installation date is less than 1942
            if ($refFloorInstallationDate < '1942-04-01' && $refPropertyType == 2) {
                $arvCalcPercFactor += 10;
            }
        } else                                                                         // If The Property floor is not residential
            $arvCalcPercFactor += 15;
        // Total ARV and other Taxes
        $arv = ($tempArv * $arvCalcPercFactor) / 100;

        $holdingTax = ($arv * 12.5) / 100;
        $latrineTax = ($arv * 7.5) / 100;
        $waterTax = ($arv * 7.5) / 100;
        $healthTax = ($arv * 6.25) / 100;
        $educationTax = ($arv * 5.0) / 100;
        $rwhPenalty = 0;

        $totalTax = $holdingTax + $latrineTax + $waterTax + $healthTax + $educationTax + $rwhPenalty;

        // Tax Calculation Quaterly
        $tax = [
            "arv" => roundFigure($arv / 4),
            "holdingTax" => roundFigure($holdingTax / 4),
            "latrineTax" => roundFigure($latrineTax / 4),
            "waterTax" => roundFigure($waterTax / 4),
            "healthTax" => roundFigure($healthTax / 4),
            "educationTax" => roundFigure($educationTax / 4),
            "rwhPenalty" => roundFigure($rwhPenalty),
            "totalTax" => roundFigure($totalTax / 4)
        ];
        return $tax;
    }

    /**
     * | RuleSet2
     */
    public function calculateRuleSet2()
    {
    }

    /**
     * | RuleSet3
     */
    public function calculateRuleSet3()
    {
    }
}
