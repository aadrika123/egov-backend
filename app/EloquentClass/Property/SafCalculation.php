<?php

namespace App\EloquentClass\Property;

use App\Models\Property\PropMBuildingRentalRate;
use App\Models\Property\PropMCapitalValueRateRaw;
use App\Models\Property\PropMRentalValue;
use App\Models\Property\PropMUsageTypeMultiFactor;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Psy\ConfigPaths;

/**
 * | --------- Saf Calculation Class -----------------
 * | Created On - 12-10-2022 
 * | Created By - Anshu Kumar
 */
class SafCalculation
{

    private array $_GRID;
    private array $_propertyDetails;
    private array $_floors;
    private $_isResidential;
    private $_ruleSets;
    private $_ulbId;
    private $_rentalValue;
    private $_multiFactorRule2;
    private $_multiFactorRule3;
    private $_paramRentalRate;              // 144
    private $_rentalRatesRule2;
    private $_rentalRatesRule3;
    private $_todayDate;
    private $_virtualDate;
    private $_effectiveDateRule2;
    private $_effectiveDateRule3;
    private array $_readRoadType;
    private $_capitalValueRate;

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
            $this->_propertyDetails = $req->all();
            $this->readPropertyMasterData();                                // Make all master data as global
            $readPropertyType = $req->propertyType;
            // Means the Property Type is not a vacant Land
            if ($readPropertyType != 4) {
                $floors = $this->_floors;
                // readTaxCalculation Floor Wise
                $calculateFloorTaxQuaterly = collect($floors)->map(function ($floor, $key) {
                    $calculateQuaterlyRuleSets = $this->calculateQuaterlyRulesets($key);
                    return $calculateQuaterlyRuleSets;
                });
                // Collapsion of the all taxes which contains saperately array collection
                $readFinalFloorTax = collect($calculateFloorTaxQuaterly)->collapse();
                $this->_GRID['details'] = $readFinalFloorTax;
            }

            return responseMsg(true, "Data Fetched", remove_null($this->_GRID));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Make All Master Data in a Global Variable (1.1)
     */

    private function readPropertyMasterData()
    {
        $propertyDetails = $this->_propertyDetails;
        $this->_paramRentalRate = Config::get("PropertyConstaint.PARAM_RENTAL_RATE");

        $this->_effectiveDateRule2 = Config::get("PropertyConstaint.EFFECTIVE_DATE_RULE2");
        $this->_effectiveDateRule3 = Config::get("PropertyConstaint.EFFECTIVE_DATE_RULE3");

        $this->_todayDate = Carbon::now();
        $this->_virtualDate = $this->_todayDate->subYears(12)->format('Y-m-d');
        $this->_floors = $propertyDetails['floor'];

        // Check If the one of the floors is commercial
        $readCommercial = collect($this->_floors)->where('useType', '!=', 1);
        $this->_isResidential = $readCommercial->isEmpty();
        $this->_ulbId = auth()->user()->ulb_id;

        $this->_rentalValue = $this->readRentalValue();                                              // Calculation of Rental Values and Storing in Global Variable (function 1.1.1)
        $this->_multiFactorRule2 = $this->readMultiFactor($this->_effectiveDateRule2);               // Calculation of Multi Factors and Storing in Global Variable $_multiFactor (function 1.1.2)
        $this->_multiFactorRule3 = $this->readMultiFactor($this->_effectiveDateRule3);               // Calculation of Multi Factors and Storing in Global Variable $_multiFactor (function 1.1.2)
        $this->_readRoadType[$this->_effectiveDateRule2] = $this->readRoadType($this->_effectiveDateRule2);         // Road Type ID According to ruleset2 effective Date
        $this->_readRoadType[$this->_effectiveDateRule3] = $this->readRoadType($this->_effectiveDateRule3);         // Road Type id according to ruleset3 effective Date
        $this->_rentalRatesRule2 = $this->calculateRentalRatesRule($this->_effectiveDateRule2);      // Calculate and collection of all Rental Rate for all floors (function 1.1.3)
        $this->_rentalRatesRule3 = $this->calculateRentalRatesRule($this->_effectiveDateRule3);      // Calculate and collection of all Rental Rate for all floors (function 1.1.3)

        $this->_capitalValueRate = $this->readCapitalvalueRate();                                    // Calculate Capital Value Rate 
    }

    /**
     * | Rental Value Calculation (1.1.1)
     */
    public function readRentalValue()
    {
        $readRentalValue = collect($this->_floors)->map(function ($floors) {
            $readZoneId = $this->_propertyDetails['zone'];
            $readUlbId = $this->_ulbId;
            $readUsageType = $floors['useType'];
            $readConstructionType = $floors['constructionType'];
            $refRentalValue = PropMRentalValue::select('rate')
                ->where('usage_types_id', $readUsageType)
                ->where('zone_id', $readZoneId)
                ->where('construction_types_id', $readConstructionType)
                ->where('ulb_id', $readUlbId)
                ->where('status', 1)
                ->first();
            return $refRentalValue;
        });
        return $readRentalValue;
    }

    /**
     * | MultiFactor Calculation (1.1.2)
     */
    public function readMultiFactor($effectiveDate)
    {
        $readMultiFactor = collect($this->_floors)->map(function ($floors) use ($effectiveDate) {
            $readFloorUsageType = $floors['useType'];
            $refMultiFactor = PropMUsageTypeMultiFactor::select('multi_factor')
                ->where('usage_type_id', $readFloorUsageType)
                ->where('effective_date', $effectiveDate)
                ->first();
            return $refMultiFactor;
        });
        return $readMultiFactor;
    }

    /**
     * | Read Road Type
     * | @param effectiveDate according to the RuleSet
     * | @return roadTypeId
     */
    public function readRoadType($effectiveDate)
    {
        $readRoadWidth = $this->_propertyDetails['roadType'];

        $queryRoadType = "SELECT * FROM prop_m_road_types
                          WHERE range_from_sqft<=ROUND($readRoadWidth)
                          AND effective_date = '$effectiveDate'
                          ORDER BY range_from_sqft DESC LIMIT 1";

        $refRoadType = DB::select($queryRoadType);
        $roadTypeId = $refRoadType[0]->prop_road_typ_id;
        return $roadTypeId;
    }

    /**
     * | Read Capital Value Rate for the calculation of Building RuleSet 3
     */
    public function readCapitalValueRate()
    {
        $readFloors = $this->_floors;
        // Capital Value Rate
        $readPropertyType = $this->_propertyDetails['propertyType'] == 1 ? 1 : 2;
        $col1 = Config::get("PropertyConstaint.CIRCALE-RATE-USAGE.$readPropertyType");

        $readRoadType = $this->_readRoadType[$this->_effectiveDateRule3];
        $col3 = Config::get("PropertyConstaint.CIRCALE-RATE-ROAD.$readRoadType");

        $capitalValue = collect($readFloors)->map(function ($readfloor) use ($col1, $col3) {

            $readConstructionType = $readfloor['constructionType'];
            $col2 = Config::get("PropertyConstaint.CIRCALE-RATE-PROP.$readConstructionType");
            $column = $col1 . $col2 . $col3;

            $capitalValueRate = PropMCapitalValueRateRaw::select($column)
                ->where('ulb_id', $this->_ulbId)
                ->where('ward_no', 1)                                                           // Ward No Fixed 
                ->first();
            return $capitalValueRate->$column;
        });
        return $capitalValue;
    }

    /**
     * | Calculation Rental Rate (1.1.3)
     * | @var queryRoadType Query for the execution for the road Type Id
     * | @var refRoadType the Road Type ID for the Property
     * | @var refParamRentalRate Rental Rate Parameter to calculate rentalRate for the Property
     * | ------------------------ Calculation -------------------------------------------------
     * | $calculatedRentalRate = $refParamRentalRate->rate * $paramRentalRate;
     * | --------------------------------------------------------------------------------------
     * | Dump 
     * | @return readRentalRate final Calculated Rental Rate
     */
    public function calculateRentalRatesRule($effectiveDate)
    {
        $roadTypeId = $this->_readRoadType[$effectiveDate];

        $readRentalRate = collect($this->_floors)->map(function ($floors) use ($effectiveDate, $roadTypeId) {
            $paramRentalRate = $this->_paramRentalRate;
            $readConstructionType = $floors['constructionType'];
            $refParamRentalRate = PropMBuildingRentalRate::where('prop_road_type_id', $roadTypeId)
                ->where('construction_types_id', $readConstructionType)
                ->where('effective_date', $effectiveDate)
                ->first();

            $calculatedRentalRate = $refParamRentalRate->rate * $paramRentalRate;
            return $calculatedRentalRate;
        });
        return $readRentalRate;
    }

    /**
     * | Calculate Quaterly Rulesets (1.2)
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
    public function calculateQuaterlyRulesets($key)
    {
        $readDateFrom = $this->_propertyDetails['floor'][$key]['dateFrom'];
        $readDateUpto = $this->_propertyDetails['floor'][$key]['dateUpto'];
        $arrayRuleSet = [];

        $carbonDateFrom = Carbon::parse($readDateFrom)->format('Y-m-d');
        $carbonDateUpto = Carbon::parse($readDateUpto)->format('Y-m-d');

        if ($readDateUpto == null) {
            $readDateUpto = $this->_todayDate->format('Y-m-d');
        }

        if ($readDateFrom > $this->_virtualDate) {
            $dateFrom = $readDateFrom;
        }

        if ($dateFrom < $this->_virtualDate) {
            $dateFrom = $this->_virtualDate;
        }

        // Itteration for the RuleSets dateFrom wise 
        while ($carbonDateFrom <= $carbonDateUpto) {
            $readRuleSet = $this->readRuleSet($carbonDateFrom, $key);
            $carbonDateFrom = Carbon::parse($carbonDateFrom)->addMonth()->format('Y-m-d');              // CarbonDateFrom = CarbonDateFrom + 1 (add one months)
            array_push($arrayRuleSet, $readRuleSet);
        }

        $collectRuleSets = collect($arrayRuleSet);
        $uniqueRuleSets = $collectRuleSets->unique('dueDate');
        $ruleSet = $uniqueRuleSets->values();

        $this->_ruleSets = $ruleSet;
        return $this->_ruleSets;
    }

    /**
     * | Get Rule Set (1.2.1)
     * | --------------------- Initialization ---------------------- | 
     * | @param dateFrom Installation Date of floor or Property
     * | @var ruleSets contains the ruleSet in an array
     * | Query Run Time - 3
     */
    public function readRuleSet($dateFrom, $key)
    {
        $readFloorDetail =
            [
                'floorNo' => $this->_floors[$key]['floorNo'],
                'useType' => $this->_floors[$key]['useType'],
                'constructionType' => $this->_floors[$key]['constructionType'],
                'buildupArea' => $this->_floors[$key]['buildupArea'],
                'dateFrom' => $this->_floors[$key]['dateFrom'],
                'dateTo' => $this->_floors[$key]['dateUpto']
            ];

        // is implimented rule set 1 (before 2016-2017), (2016-2017 TO 2021-2022), (2021-2022 TO TILL NOW)
        if ($dateFrom < $this->_effectiveDateRule2) {
            $ruleSets[] = [
                "quarterYear" => calculateFyear($dateFrom),                              // Calculate Financial Year means to Calculate the FinancialYear
                "ruleSet" => "RuleSet1",
                "qtr" => calculateQtr($dateFrom),                                        // Calculate Quarter from the date
                "dueDate" => calculateQuaterDueDate($dateFrom),                          // Calculate Quarter Due Date of the Date

            ];
            $tax = $this->calculateRuleSet1($key);                                       // Tax Calculation
            $ruleSetsWithTaxes = array_merge($readFloorDetail, $ruleSets[0], $tax);
            return $ruleSetsWithTaxes;
        }
        // is implimented rule set 2 (2016-2017 TO 2021-2022), (2021-2022 TO TILL NOW)
        if ($dateFrom < $this->_effectiveDateRule3) {
            $ruleSets[] = [
                "quarterYear" => calculateFyear($dateFrom),
                "ruleSet" => "RuleSet2",
                "qtr" => calculateQtr($dateFrom),
                "dueDate" => calculateQuaterDueDate($dateFrom)
            ];
            $tax = $this->calculateRuleSet2($key);
            $ruleSetsWithTaxes = array_merge($readFloorDetail, $ruleSets[0], $tax);
            return $ruleSetsWithTaxes;
        }

        // is implimented rule set 3 (2021-2022 TO TILL NOW)
        if ($dateFrom >= $this->_effectiveDateRule3) {
            $ruleSets[] = [
                "quarterYear" => calculateFyear($dateFrom),
                "ruleSet" => "RuleSet3",
                "qtr" => calculateQtr($dateFrom),
                "dueDate" => calculateQuaterDueDate($dateFrom)
            ];
            $tax = $this->calculateRuleSet3($key);
            $ruleSetsWithTaxes = array_merge($readFloorDetail, $ruleSets[0], $tax);
            return $ruleSetsWithTaxes;
        }
    }

    /**
     * | Calculation of Property Tax By RuleSet 1 (1.2.1.1)
     * ------------------------------------------------------------------
     * | @param key keyvalue of the array of The Floor
     * ------------------ Initialization --------------------------------
     * | @var readBuildupArea buildup area for the floor 
     * | @var readFloorInstallationDate Floor's Installation Date
     * | @var readUsageType floor Usage Type ID
     * | @var readOccupancyType floorOccupancy Type
     * | @var readPropertyType Property type 
     * | @var readRentalValue the rental value for the floor
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
    public function calculateRuleSet1($key)
    {
        $readBuildupArea =  $this->_floors[$key]['buildupArea'];
        $readFloorInstallationDate =  $this->_floors[$key]['dateFrom'];
        $readUsageType = $this->_floors[$key]['useType'];
        $readOccupancyType = $this->_floors[$key]['occupancyType'];
        $readPropertyType = $this->_propertyDetails['propertyType'];

        $readRentalValue = $this->_rentalValue[$key];

        if (!$readRentalValue) {
            return responseMsg(false, "Rental Value Not Available", "");
        }

        $tempArv = $readBuildupArea * (float)$readRentalValue->rate;
        $arvCalcPercFactor = 0;

        if ($readUsageType == 1 && $readOccupancyType == 2) {
            $arvCalcPercFactor += 30;
            // Condition if the property is Independent Building and installation date is less than 1942
            if ($readFloorInstallationDate < '1942-04-01' && $readPropertyType == 2) {
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
     * | RuleSet 2 Calculation (1.2.1.2)
     * ---------------------- Initialization -------------------
     * | @param key array key index
     * | @var readFloorUsageType Floor Usage Type(Residential or Other)
     * | @var readFloorBuildupArea Floor Buildup Area(SqFt)
     * | @var readFloorOccupancyType Floor Occupancy Type (Self or Tenant)
     * | @var readAreaOfPlot Property Road Width
     * | @var refConstructionType Floor Construction Type ID
     * | @var paramCarpetAreaPerc (70% -> Residential || 80% -> Commercial)
     * | @var paramOccupancyFactor (Self-1 || Rent-1.5)
     * | @var readMultiFactor Get the MultiFactor Using PropUsageTypeMultiFactor Table
     * | @var tempArv = temperory ARV for the Reference to calculate @var arv
     * ---------------------- Calculation ----------------------
     * | $reAreaOfPlot = areaOfPlot * 435.6 (In SqFt)
     * | $refParamCarpetAreaPerc (Residential-70%,Commercial-80%)
     * | $carpetArea = $refFloorBuildupArea x $paramCarpetAreaPerc %
     * | $rentalRate Calculation of RentalRate Using Current Object Function
     * | $tempArv = $carpetArea * ($readMultiFactor->multi_factor) * $paramOccupancyFactor * $rentalRate;
     * | $arv = ($tempArv * 2) / 100;
     * | $rwhPenalty = $arv/2
     * | $totalTax = $arv + $rwhPenalty;
     */
    public function calculateRuleSet2($key)
    {
        $propertyDetails = $this->_propertyDetails;
        $readFloorUsageType = $this->_floors[$key]['useType'];
        $readFloorBuildupArea = $this->_floors[$key]['buildupArea'];
        $readFloorOccupancyType = $this->_floors[$key]['occupancyType'];
        $paramCarpetAreaPerc = ($readFloorUsageType == 1) ? 70 : 80;
        $paramOccupancyFactor = ($readFloorOccupancyType == 2) ? 1 : 1.5;
        $readAreaOfPlot =  $this->_propertyDetails['areaOfPlot'] * 435.6;                                    // (In Decimal To SqFt)

        $readMultiFactor = $this->_multiFactorRule2[$key];

        $carpetArea = ($readFloorBuildupArea * $paramCarpetAreaPerc) / 100;
        $rwhPenalty = 0;

        // Rental Rate Calculation
        $rentalRate = $this->_rentalRatesRule2[$key];
        $tempArv = $carpetArea * ($readMultiFactor->multi_factor) * $paramOccupancyFactor * $rentalRate;
        $arv = ($tempArv * 2) / 100;

        // Rain Water Harvesting Penalty If The Plot Area is Greater than 3228 sqft. and Rain Water Harvesting is none
        if ($propertyDetails['isWaterHarvesting'] == 1 && $readAreaOfPlot > 3228) {
            $rwhPenalty = $arv / 2;
        }
        $totalTax = $arv + $rwhPenalty;
        // All Taxes Quaterly
        $tax = [
            "arv" => roundFigure($arv / 4),
            "holdingTax" => 0,
            "latrineTax" => 0,
            "waterTax" => 0,
            "healthTax" => 0,
            "educationTax" => 0,
            "rwhPenalty" => roundFigure($rwhPenalty / 4),
            "totalTax" => roundFigure($totalTax / 4)
        ];
        return $tax;
    }

    /**
     * | RuleSet 3 Calculation (1.2.1.3)
     * | @param key arrayKey value
     */
    public function calculateRuleSet3($key)
    {
        $readCircleRate = $this->_capitalValueRate[$key];

        $readBuildupArea = $this->_floors[$key]['buildupArea'];

        $readFloorOccupancyType = $this->_floors[$key]['occupancyType'];
        $paramOccupancyFactor = ($readFloorOccupancyType == 2) ? 1 : 1.5;

        $readUsageType = $this->_floors[$key]['useType'];
        $taxPerc = ($readUsageType == 1) ? 0.075 : 0.15;                                                // 0.075 for Residential and 0.15 for Commercial

        $readCalculationFactor = $this->_multiFactorRule3[$key]->multi_factor;                          // (Calculation Factor as Multi Factor)
        $readMatrixFactor = $this->_rentalRatesRule3[$key];                                             // (Matrix Factor as Rental Rate)

        $calculatePropertyTax = ($readCircleRate * $readBuildupArea * $paramOccupancyFactor * $taxPerc * (float)$readCalculationFactor * (float)$readMatrixFactor) / 100;
        // Tax Calculation Quaterly
        $tax = [
            "arv" => $calculatePropertyTax / 4,
            "holdingTax" => 0
        ];
        return $tax;
    }
}
