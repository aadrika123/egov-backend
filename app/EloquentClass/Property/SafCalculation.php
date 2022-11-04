<?php

namespace App\EloquentClass\Property;

use App\Models\Property\PropMBuildingRentalRate;
use App\Models\Property\PropMRentalValue;
use App\Models\Property\PropMUsageTypeMultiFactor;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

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
    private $_multiFactor;
    private $_paramRentalRate;              // 144
    private $_rentalRate;


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
            $this->readPropertyMasterData();
            $refPropertyType = $req->propertyType;
            // Means the Property Type is not a vacant Land
            if ($refPropertyType != 4) {
                $floors = $this->_floors;

                foreach ($floors as $key => $refFloor) {
                    // Quaterly RuleSet Calculation
                    $refQuaterlyRuleSets = $this->calculateQuaterlyRulesets($key);
                    $this->_GRID['details'][$key] = $refQuaterlyRuleSets;
                }
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

        $this->_floors = $propertyDetails['floor'];
        // Check If the one of the floors is commercial
        $readCommercial = collect($this->_floors)->where('useType', '!=', 1);
        $this->_isResidential = $readCommercial->isEmpty();

        $this->_ulbId = auth()->user()->ulb_id;

        // Calculation of Rental Values and Storing in Global Variable
        $floor = $this->_floors;
        $refRentalValue = collect($floor)->map(function ($floors) {
            $zoneId = $this->_propertyDetails['zone'];
            $refUlbId = $this->_ulbId;
            $refUsageType = $floors['useType'];
            $refConstructionType = $floors['constructionType'];
            $rentalValue = PropMRentalValue::select('rate')
                ->where('usage_types_id', $refUsageType)
                ->where('zone_id', $zoneId)
                ->where('construction_types_id', $refConstructionType)
                ->where('ulb_id', $refUlbId)
                ->where('status', 1)
                ->first();
            return $rentalValue;
        });
        $this->_rentalValue = $refRentalValue;

        // Calculation of Multi Factors and Storing in Global Variable $_multiFactor
        $refMultiFactor = collect($floor)->map(function ($floors) {
            $refFloorUsageType = $floors['useType'];
            $multiFactor = PropMUsageTypeMultiFactor::select('multi_factor')
                ->where('usage_type_id', $refFloorUsageType)
                ->first();
            return $multiFactor;
        });
        $this->_multiFactor = $refMultiFactor;

        // Calculate Rental Rate
        $this->_rentalRate = $this->calculateRentalRate();
    }

    /**
     * | Calculation Rental Rate (1.1.3)
     * | @param refRoadWidth Property Width
     * | @param refConstruction Floor Construction Type
     * | @var paramRentalRate Parameter value to calculate the rentalRate
     * | @var queryRoadType Query for the execution for the road Type Id
     * | @var refRoadType the Road Type ID for the Property
     * | @var refParamRentalRate Rental Rate Parameter to calculate rentalRate for the Property
     * | ------------------------ Calculation -------------------------------------------------
     * | $rentalRate = $refParamRentalRate->rate * $paramRentalRate;
     * | --------------------------------------------------------------------------------------
     * | Dump 
     * | @return rentalRate final Calculated Rental Rate
     */
    public function calculateRentalRate()
    {
        $refRoadWidth = $this->_propertyDetails['roadType'];

        $queryRoadType = "SELECT * FROM prop_m_road_types
                          WHERE range_from_sqft<=ROUND($refRoadWidth) ORDER BY range_from_sqft DESC LIMIT 1";

        $refRoadType = DB::select($queryRoadType);
        $this->_roadTypeId = $refRoadType[0]->prop_road_typ_id;
        $floor = $this->_floors;

        $rentalRate = collect($floor)->map(function ($floors) {
            $paramRentalRate = $this->_paramRentalRate;
            $refConstructionType = $floors['constructionType'];
            $refParamRentalRate = PropMBuildingRentalRate::where('prop_road_type_id', $this->_roadTypeId)
                ->where('construction_types_id', $refConstructionType)
                ->first();

            $calculatedRentalRate = $refParamRentalRate->rate * $paramRentalRate;
            return $calculatedRentalRate;
        });
        return $rentalRate;
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
    public function calculateQuaterlyRulesets($key)
    {
        $propertyDetails = $this->_propertyDetails;
        $dateFrom = $propertyDetails['floor'][$key]['dateFrom'];
        $dateUpTo = $propertyDetails['floor'][$key]['dateUpto'];
        $ruleSet = $this->_ruleSets;

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
                'floorNo' => $propertyDetails['floor'][$key]['floorNo'],
                'useType' => $propertyDetails['floor'][$key]['useType'],
                'constructionType' => $propertyDetails['floor'][$key]['constructionType'],
                'buildupArea' => $propertyDetails['floor'][$key]['buildupArea'],
                'dateFrom' => $propertyDetails['floor'][$key]['dateFrom'],
                'dateTo' => $propertyDetails['floor'][$key]['dateUpto']
            ];

        // Itteration for the RuleSets dateFrom wise 
        while ($carbonDateFrom <= $carbonDateUpto) {
            $refRuleSet = $this->readRuleSet($carbonDateFrom, $key);
            $carbonDateFrom = Carbon::parse($carbonDateFrom)->addMonth()->format('Y-m-d');              // CarbonDateFrom = CarbonDateFrom + 1 (add one months)
            array_push($arrayRuleSet, $refRuleSet[0]);
        }

        $collectRuleSets = collect($arrayRuleSet);
        $uniqueRuleSets = $collectRuleSets->unique('dueDate');

        $ruleSet = $floorDetail;
        $ruleSet['ruleSets'] = $uniqueRuleSets->values();

        $this->_ruleSets = $ruleSet;
        return $this->_ruleSets;
    }

    /**
     * | Get Rule Set (1.1.1)
     * | --------------------- Initialization ---------------------- | 
     * | @param dateFrom Installation Date of floor or Property
     * | @var ruleSets contains the ruleSet in an array
     * | Query Run Time - 3
     */
    public function readRuleSet($dateFrom, $key)
    {
        // is implimented rule set 1 (before 2016-2017), (2016-2017 TO 2021-2022), (2021-2022 TO TILL NOW)
        if ($dateFrom < "2016-04-01") {
            $ruleSets[] = [
                "quarterYear" => calculateFyear($dateFrom),                              // Calculate Financial Year means to Calculate the FinancialYear
                "ruleSet" => "RuleSet1",
                "qtr" => calculateQtr($dateFrom),                                        // Calculate Quarter from the date
                "dueDate" => calculateQuaterDueDate($dateFrom),                          // Calculate Quarter Due Date of the Date
                "tax" => $this->calculateRuleSet1($key)                                  // Tax Calculation
            ];
            return $ruleSets;
        }
        // is implimented rule set 2 (2016-2017 TO 2021-2022), (2021-2022 TO TILL NOW)
        if ($dateFrom < "2022-04-01") {
            $ruleSets[] = [
                "quarterYear" => calculateFyear($dateFrom),
                "ruleSet" => "RuleSet2",
                "qtr" => calculateQtr($dateFrom),
                "dueDate" => calculateQuaterDueDate($dateFrom),
                "tax" => $this->calculateRuleSet2($key)
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
     * | Calculation of Property Tax By RuleSet 1 (1.1.1.1)
     * ------------------------------------------------------------------
     * | @param propertyDetails request input of all the property details
     * | @param key keyvalue of the array of The Floor
     * ------------------ Initialization --------------------------------
     * | @var refBuildupArea buildup area for the floor 
     * | @var refFloorInstallationDate Floor's Installation Date
     * | @var refUsageType floor Usage Type ID
     * | @var refOccupancyType floorOccupancy Type
     * | @var refPropertyType Property type 
     * | @var rentalValue the rental value for the floor
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
        $propertyDetails = $this->_propertyDetails;
        $refBuildupArea = $propertyDetails['floor'][$key]['buildupArea'];
        $refFloorInstallationDate = $propertyDetails['floor'][$key]['dateFrom'];
        $refUsageType = $propertyDetails['floor'][$key]['useType'];
        $refOccupancyType = $propertyDetails['floor'][$key]['occupancyType'];
        $refPropertyType = $propertyDetails['propertyType'];

        $rentalValue = $this->_rentalValue[$key];

        if (!$rentalValue) {
            return responseMsg(false, "Rental Value Not Available", "");
        }
        $tempArv = $refBuildupArea * (float)$rentalValue->rate;
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
     * | RuleSet 2 Calculation (1.1.1.2)
     * ---------------------- Initialization -------------------
     * | @param propertyDetails request input of property details
     * | @param key array key index
     * | @var refFloorUsageType Floor Usage Type(Residential or Other)
     * | @var refFloorBuildupArea Floor Buildup Area(SqFt)
     * | @var refFloorOccupancyType Floor Occupancy Type (Self or Tenant)
     * | @var refRoadWidth Property Road Width
     * | @var refConstructionType Floor Construction Type ID
     * | @var paramCarpetAreaPerc (70% -> Residential || 80% -> Commercial)
     * | @var paramOccupancyFactor (Self-1 || Rent-1.5)
     * | @var refMultiFactor Get the MultiFactor Using PropUsageTypeMultiFactor Table
     * | @var tempArv = temperory ARV for the Reference to calculate @var arv
     * ---------------------- Calculation ----------------------
     * | $reAreaOfPlot = areaOfPlot * 435.6 (In SqFt)
     * | $refParamCarpetAreaPerc (Residential-70%,Commercial-80%)
     * | $carpetArea = $refFloorBuildupArea x $paramCarpetAreaPerc %
     * | $rentalRate Calculation of RentalRate Using Current Object Function
     * | $tempArv = $carpetArea * ($refMultiFactor->multi_factor) * $paramOccupancyFactor * $rentalRate;
     * | $arv = ($tempArv * 2) / 100;
     * | $rwhPenalty = $arv/2
     * | $totalTax = $arv + $rwhPenalty;
     */
    public function calculateRuleSet2($key)
    {
        $propertyDetails = $this->_propertyDetails;

        $refFloorUsageType = $propertyDetails['floor'][$key]['useType'];
        $refFloorBuildupArea = $propertyDetails['floor'][$key]['buildupArea'];
        $refFloorOccupancyType = $propertyDetails['floor'][$key]['occupancyType'];
        $paramCarpetAreaPerc = ($refFloorUsageType == 1) ? 70 : 80;
        $paramOccupancyFactor = ($refFloorOccupancyType == 2) ? 1 : 1.5;
        $refAreaOfPlot = $propertyDetails['areaOfPlot'] * 435.6;                                    // (In Decimal To SqFt)

        $refMultiFactor = $this->_multiFactor[$key];

        $carpetArea = ($refFloorBuildupArea * $paramCarpetAreaPerc) / 100;
        $rwhPenalty = 0;

        // Rental Rate Calculation
        $rentalRate = $this->_rentalRate[$key];                                                     // Calculate Rental Rate

        $tempArv = $carpetArea * ($refMultiFactor->multi_factor) * $paramOccupancyFactor * $rentalRate;
        $arv = ($tempArv * 2) / 100;

        // Rain Water Harvesting Penalty If The Plot Area is Greater than 3228 sqft. and Rain Water Harvesting is none
        if ($propertyDetails['isWaterHarvesting'] == 1 && $refAreaOfPlot > 3228) {
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
     * | RuleSet3
     */
    public function calculateRuleSet3()
    {
    }
}
