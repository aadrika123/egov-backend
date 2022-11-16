<?php

namespace App\EloquentClass\Property;

use App\Models\Property\PropMBuildingRentalConst;
use App\Models\Property\PropMBuildingRentalRate;
use App\Models\Property\PropMCapitalValueRateRaw;
use App\Models\Property\PropMRentalValue;
use App\Models\Property\PropMUsageTypeMultiFactor;
use App\Models\Property\PropMVacantRentalRate;
use App\Models\UlbWardMaster;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

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
    private $_wardNo;
    private $_isResidential;
    private $_ruleSets;
    private $_ulbId;
    private $_rentalValue;
    private $_multiFactors;
    private $_paramRentalRate;
    private $_rentalRates;
    private $_virtualDate;
    private $_effectiveDateRule2;
    private $_effectiveDateRule3;
    private array $_readRoadType;
    private $_capitalValueRate;
    private bool $_rwhPenaltyStatus = false;
    private $_mobileTowerArea;
    private $_mobileTowerInstallDate;
    private array $_hoardingBoard;
    private array $_petrolPump;
    private $_mobileQuaterlyRuleSets;
    private $_hoardingQuaterlyRuleSets;
    private $_petrolPumpQuaterlyRuleSets;
    private $_vacantRentalRates;
    private $_vacantPropertyTypeId;
    private $_currentQuarterStartDate;
    private $_currentQuarterDate;
    private $_loggedInUserType;
    private $_redis;

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
            $apiId = [
                "status" => true,
                "uniqueId" => 'SAF/01',
                "version" => "1",
                "ts" => "",
                "action" => "POST",
                "did" => ""
            ];

            $this->_propertyDetails = $req->all();

            $this->readPropertyMasterData();                                                        // Make all master data as global(1.1)

            $this->calculateMobileTowerTax();                                                       // For Mobile Towers(1.2)

            $this->calculateHoardingBoardTax();                                                     // For Hoarding Board(1.3)

            $this->calculateBuildingTax();                                                          // Means the Property Type is a Building(1.4)

            $this->calculateVacantLandTax();                                                        // If The Property Type is the type of Vacant Land(1.5)

            $this->calculateFinalPayableAmount();                                                   // Adding Total Final Tax with fine and Penalties(1.6)

            $collection = collect($this->_GRID)->reverse();                                         // Final Collection of the Contained Grid
            return responseMsg(true, $this->summarySafCalculation(), remove_null($collection));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Make All Master Data in a Global Variable (1.1)
     */

    public function readPropertyMasterData()
    {
        $this->_redis = Redis::connection();
        $propertyDetails = $this->_propertyDetails;
        $this->_paramRentalRate = Config::get("PropertyConstaint.PARAM_RENTAL_RATE");

        $this->_effectiveDateRule2 = Config::get("PropertyConstaint.EFFECTIVE_DATE_RULE2");
        $this->_effectiveDateRule3 = Config::get("PropertyConstaint.EFFECTIVE_DATE_RULE3");

        $todayDate = Carbon::now();
        $this->_virtualDate = $todayDate->subYears(12)->format('Y-m-d');
        $this->_floors = $propertyDetails['floor'];
        $this->_ulbId = auth()->user()->ulb_id;

        $this->_vacantPropertyTypeId = Config::get("PropertyConstaint.VACANT_PROPERTY_TYPE");               // Vacant Property Type Id

        // Ward No
        $this->_wardNo = Redis::get('ulbWardMaster:' . $propertyDetails['ward']);                           // Ward No Value from Redis
        if (!$this->_wardNo) {
            $this->_wardNo = UlbWardMaster::find($propertyDetails['ward'])->ward_name;
            $this->_redis->set('ulbWardMaster:' . $propertyDetails['ward'], $this->_wardNo);
        }

        // Rain Water Harvesting Penalty If The Plot Area is Greater than 3228 sqft. and Rain Water Harvesting is none
        $readAreaOfPlot =  $this->_propertyDetails['areaOfPlot'] * 435.6;                                    // (In Decimal To SqFt)
        if ($propertyDetails['propertyType'] != $this->_vacantPropertyTypeId && $propertyDetails['isWaterHarvesting'] == 0 && $readAreaOfPlot > 3228) {
            $this->_rwhPenaltyStatus = true;
        }

        $refParamRentalRate = json_decode(Redis::get('propMBuildingRentalConst:' . $this->_ulbId));         // Get Building Rental Constant From Redis

        if (!$refParamRentalRate) {                                                                         // Get Building Rental Constant From Database
            $refParamRentalRate = PropMBuildingRentalConst::where('ulb_id', $this->_ulbId)->first();
            $this->_redis->set('propMBuildingRentalConst:' . $this->_ulbId, json_encode($refParamRentalRate));
        }

        $this->_paramRentalRate = $refParamRentalRate->x;

        // Check If the one of the floors is commercial for Building
        if ($this->_propertyDetails['propertyType'] != $this->_vacantPropertyTypeId) {
            $readCommercial = collect($this->_floors)->where('useType', '!=', 1);
            $this->_isResidential = $readCommercial->isEmpty();
        }

        // Check if the vacant land is residential or not
        if ($propertyDetails['propertyType'] == $this->_vacantPropertyTypeId) {
            $condition = $propertyDetails['isMobileTower'] == true || $propertyDetails['isHoardingBoard'] == true;
            $this->_isResidential = $condition ? false : true;
        }

        $this->_rentalValue = $this->readRentalValue();
        $this->_multiFactors = $this->readMultiFactor();                                                            // Calculation of Rental rate and Storing in Global Variable (function 1.1.1)
        $this->_readRoadType[$this->_effectiveDateRule2] = $this->readRoadType($this->_effectiveDateRule2);         // Road Type ID According to ruleset2 effective Date
        $this->_readRoadType[$this->_effectiveDateRule3] = $this->readRoadType($this->_effectiveDateRule3);         // Road Type id according to ruleset3 effective Date
        $this->_rentalRates = $this->calculateRentalRates();
        $this->_capitalValueRate = $this->readCapitalvalueRate();                                                   // Calculate Capital Value Rate 
        if ($propertyDetails['isMobileTower'] == 1 || $propertyDetails['isHoardingBoard'] == 1 || $propertyDetails['isPetrolPump'] == 1) {
            $this->_capitalValueRateMPH = $this->readCapitalValueRateMHP();                                         // Capital Value Rate for MobileTower, PetrolPump,HoardingBoard
        }

        if ($this->_propertyDetails['propertyType'] == 4) {                                                         // i.e for Vacant Land
            $this->_vacantRentalRates = $this->readVacantRentalRates();
        }

        // Current Quarter End Date and Start Date for 1% Penalty
        $current = Carbon::now()->format('Y-m-d');
        $currentQuarterDueDate = Carbon::parse(calculateQuaterDueDate($current))->floorMonth();
        $this->_currentQuarterDueDate = $currentQuarterDueDate;                                                     // Quarter Due Date
        $currentQuarterDate = Carbon::parse($current)->floorMonth();
        $this->_currentQuarterDate = $currentQuarterDate;                                                           // Quarter Current Date
        $this->_loggedInUserType = auth()->user()->user_type;                                                       // User Type of current Logged In User
    }

    /**
     * | Rental Value Calculation (1.1.1)
     */
    public function readRentalValue()
    {
        $readZoneId = $this->_propertyDetails['zone'];
        $refRentalValue = json_decode(Redis::get('propMRentalValue-z-' . $readZoneId . '-u-' . $this->_ulbId));         // Get Rental Value from Redis
        if (!$refRentalValue) {
            $refRentalValue = PropMRentalValue::select('usage_types_id', 'zone_id', 'construction_types_id', 'rate')    // Get Rental value from DB
                ->where('zone_id', $readZoneId)
                ->where('ulb_id', $this->_ulbId)
                ->where('status', 1)
                ->get();
            $this->_redis->set('propMRentalValue-z-' . $readZoneId . '-u-' . $this->_ulbId, json_encode($refRentalValue));
        }
        return $refRentalValue;
    }

    /**
     * | MultiFactor Calculation (1.1.2)
     */
    public function readMultiFactor()
    {
        $refMultiFactor = json_decode(Redis::get('propMUsageTypeMultiFactor'));                                      // Get Usage Type Multi Factor From Redis
        if (!$refMultiFactor) {
            $refMultiFactor = PropMUsageTypeMultiFactor::select('usage_type_id', 'multi_factor', 'effective_date')   // Get Usage Type Multi Factor From DB
                ->where('status', 1)
                ->get();
            $this->_redis->set('propMUsageTypeMultiFactor', json_encode($refMultiFactor));
        }
        return $refMultiFactor;
    }

    /**
     * | Read Road Type
     * | @param effectiveDate according to the RuleSet
     * | @return roadTypeId
     */
    public function readRoadType($effectiveDate)
    {
        $readRoadWidth = $this->_propertyDetails['roadType'];

        $refRoadType = json_decode(Redis::get('roadType-effective-' . $effectiveDate . 'roadWidth-' . $readRoadWidth));
        if (!$refRoadType) {
            $queryRoadType = "SELECT * FROM prop_m_road_types
                                WHERE range_from_sqft<=ROUND($readRoadWidth)
                                AND effective_date = '$effectiveDate'
                                ORDER BY range_from_sqft DESC LIMIT 1";

            $refRoadType = collect(DB::select($queryRoadType))->first();
            $this->_redis->set('roadType-effective-' . $effectiveDate . 'roadWidth-' . $readRoadWidth, json_encode($refRoadType));
        }

        $roadTypeId = $refRoadType->prop_road_typ_id;
        return $roadTypeId;
    }

    /**
     * | Calculation Rental Rate (1.1.3)
     * | @var refParamRentalRate Rental Rate Parameter to calculate rentalRate for the Property
     * | @return readRentalRate final Calculated Rental Rate
     */
    public function calculateRentalRates()
    {
        $refParamRentalRate = json_decode(Redis::get('propMBuildingRentalRate'));
        if (!$refParamRentalRate) {
            $refParamRentalRate = PropMBuildingRentalRate::select('id', 'prop_road_type_id', 'construction_types_id', 'rate', 'effective_date', 'status')
                ->where('status', 1)
                ->get();
            $this->_redis->set('propMBuildingRentalRate', json_encode($refParamRentalRate));
        }
        return $refParamRentalRate;
    }

    /**
     * | Read Capital Value Rate for the calculation of Building RuleSet 3
     */
    public function readCapitalValueRate()
    {
        try {
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

                $capitalValueRate = json_decode(Redis::get('propMCapitalValueRateRaw-u-' . $this->_ulbId . '-w-' . $this->_wardNo . '-col-' . $column));
                if (!$capitalValueRate) {
                    $capitalValueRate = PropMCapitalValueRateRaw::select($column)
                        ->where('ulb_id', $this->_ulbId)
                        ->where('ward_no', $this->_wardNo)
                        ->first();
                    $this->_redis->set('propMCapitalValueRateRaw-u-' . $this->_ulbId . '-w-' . $this->_wardNo . '-col-' . $column, json_encode($capitalValueRate));
                }

                return $capitalValueRate->$column;
            });
            return $capitalValue;
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Read Capital Value Rate for mobile tower, Hoarding Board, Petrol Pump
     */
    public function readCapitalValueRateMHP()
    {
        $col1 = 'com';
        $col2 = '_pakka';
        $readRoadType = $this->_readRoadType[$this->_effectiveDateRule3];
        $col3 = Config::get("PropertyConstaint.CIRCALE-RATE-ROAD.$readRoadType");
        $column = $col1 . $col2 . $col3;
        $capitalValueRate = json_decode(Redis::get('propCapitalValueRateRaw-u-' . $this->_ulbId . '-w-' . $this->_wardNo . '-' . $column));         // Check Capital Value on Redis
        if (!$capitalValueRate) {
            $capitalValueRate = PropMCapitalValueRateRaw::select($column)
                ->where('ulb_id', $this->_ulbId)
                ->where('ward_no', $this->_wardNo)                                                                                                  // Ward No Fixed temprory
                ->first();
            $this->_redis->set('propCapitalValueRateRaw-u-' . $this->_ulbId . '-w-' . $this->_wardNo . '-' . $column, json_encode($capitalValueRate));
        }
        return $capitalValueRate->$column;
    }

    /**
     * | Calculate Vacant Rental Rate 
     */
    public function readVacantRentalRates()
    {
        $rentalRate = json_decode(Redis::get('propMVacantRentalRate'));
        if (!$rentalRate) {
            $rentalRate = PropMVacantRentalRate::select('id', 'prop_road_type_id', 'rate', 'ulb_type_id', 'effective_date')
                ->where('status', 1)
                ->get();
            $this->_redis->set('propMVacantRentalRate', json_encode($rentalRate));
        }
        return $rentalRate;
    }

    /**
     * | Calculate Mobile Tower (1.2)
     */
    public function calculateMobileTowerTax()
    {
        if ($this->_propertyDetails['isMobileTower'] == 1) {
            $this->_mobileTowerInstallDate = $this->_propertyDetails['mobileTower']['dateFrom'];
            $this->_mobileTowerArea = $this->_propertyDetails['mobileTower']['area'];
            $this->_mobileQuaterlyRuleSets = $this->calculateQuaterlyRulesets("mobileTower");
        }
    }

    /**
     * | In Case of the Property Have Hoarding Board(1.3)
     */
    public function calculateHoardingBoardTax()
    {
        if ($this->_propertyDetails['isHoardingBoard'] == 1) {                                                                      // For Hoarding Board
            $this->_hoardingBoard['installDate'] = $this->_propertyDetails['hoardingBoard']['dateFrom'];
            $this->_hoardingBoard['area'] = $this->_propertyDetails['hoardingBoard']['area'];
            $this->_hoardingQuaterlyRuleSets = $this->calculateQuaterlyRulesets("hoardingBoard");
        }
    }

    /**
     * | In Case of the Property is a Building or SuperStructure (1.4)
     */
    public function calculateBuildingTax()
    {
        $readPropertyType = $this->_propertyDetails['propertyType'];
        if ($readPropertyType != $this->_vacantPropertyTypeId) {
            if ($this->_propertyDetails['isPetrolPump'] == 1) {                                                                     // For Petrol Pump
                $this->_petrolPump['installDate'] = $this->_propertyDetails['petrolPump']['dateFrom'];
                $this->_petrolPump['area'] = $this->_propertyDetails['petrolPump']['area'];
                $this->_petrolPumpQuaterlyRuleSets = $this->calculateQuaterlyRulesets("petrolPump");
            }

            $floors = $this->_floors;
            // readTaxCalculation Floor Wise
            $calculateFloorTaxQuaterly = collect($floors)->map(function ($floor, $key) {
                $calculateQuaterlyRuleSets = $this->calculateQuaterlyRulesets($key);
                return $calculateQuaterlyRuleSets;
            });

            // Collapsion of the all taxes which contains saperately array collection
            $readFinalFloorTax = collect($calculateFloorTaxQuaterly)->collapse();                                                       // Collapsable collections with all Floors
            $readFinalFloorWithMobileTower = collect($this->_mobileQuaterlyRuleSets)->merge($readFinalFloorTax);                        // Collapsable Collection With Mobile Tower and Floors
            $readFinalWithMobileHoarding = collect($this->_hoardingQuaterlyRuleSets)->merge($readFinalFloorWithMobileTower);            // Collapsable Collection with mobile tower floors and Hoarding
            $readFinalWithMobilHoardingPetrolPump = collect($this->_petrolPumpQuaterlyRuleSets)->merge($readFinalWithMobileHoarding);   // Collapsable Collection With Mobile floors Hoarding and Petrol Pump
            $this->_GRID['details'] = $readFinalWithMobilHoardingPetrolPump;
        }
    }

    /**
     * | Calculate Vacant Land Tax (1.5)
     */
    public function calculateVacantLandTax()
    {
        $readPropertyType = $this->_propertyDetails['propertyType'];
        if ($readPropertyType == $this->_vacantPropertyTypeId) {
            $calculateQuaterlyRuleSets = $this->calculateQuaterlyRulesets("vacantLand");
            $ruleSetsWithMobileTower = collect($this->_mobileQuaterlyRuleSets)->merge($calculateQuaterlyRuleSets);        // Collapse with mobile tower
            $ruleSetsWithHoardingBoard = collect($this->_hoardingQuaterlyRuleSets)->merge($ruleSetsWithMobileTower);      // Collapse with hoarding board
            $this->_GRID['details'] = $ruleSetsWithHoardingBoard;
        }
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
        if (is_string($key)) {                                                          // For Mobile Tower, hoarding board or petrol pump
            $arrayRuleSet = [];
            switch ($key) {
                case "mobileTower";
                    $dateFrom = $this->_mobileTowerInstallDate;
                    break;
                case "hoardingBoard";
                    $dateFrom = $this->_hoardingBoard['installDate'];
                    break;
                case "petrolPump";
                    $dateFrom = $this->_petrolPump['installDate'];
                    break;
                case "vacantLand";
                    $dateFrom = $this->_propertyDetails['dateOfPurchase'];
                    break;
            }

            if ($dateFrom < '2016-04-01')
                $dateFrom = '2016-04-01';
            $dateTo = Carbon::now();
            $readRuleSet = $this->readRuleSet($dateFrom, $key);
            $carbonDateFrom = Carbon::parse($dateFrom)->format('Y-m-d');
            $carbonDateUpto = $dateTo->format('Y-m-d');
        }

        if (is_numeric($key)) {                                                 // i.e. Floors
            $readDateFrom = $this->_propertyDetails['floor'][$key]['dateFrom'];
            $readDateUpto = $this->_propertyDetails['floor'][$key]['dateUpto'];
            $arrayRuleSet = [];

            $carbonDateFrom = Carbon::parse($readDateFrom)->format('Y-m-d');
            $carbonDateUpto = Carbon::parse($readDateUpto)->format('Y-m-d');

            if ($readDateUpto == null) {
                $readDateUpto = Carbon::now()->format('Y-m-d');
            }

            if ($readDateFrom > $this->_virtualDate) {
                $dateFrom = $readDateFrom;
            }

            if ($dateFrom < $this->_virtualDate) {
                $dateFrom = $this->_virtualDate;
            }
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
        return $ruleSet;
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
        if (is_string($key)) {                                                                          // Mobile Tower or Hoarding Board Or Petrol Pump
            switch ($key) {
                case "mobileTower";
                    $readFloorDetail = [
                        'floorNo' => "MobileTower",
                        'buildupArea' => $this->_mobileTowerArea
                    ];
                    break;
                case "hoardingBoard";
                    $readFloorDetail = [
                        'floorNo' => "hoardingBoard",
                        'buildupArea' => $this->_hoardingBoard['area']
                    ];
                    break;
                case "petrolPump";
                    $readFloorDetail = [
                        'floorNo' => "petrolPump",
                        'buildupArea' => $this->_petrolPump['area']
                    ];
                    break;
                case "vacantLand";
                    $readFloorDetail = [
                        'propertyType' => "vacantLand",
                        'buildupArea' => $this->_propertyDetails['areaOfPlot']
                    ];
                    break;
            }
        }

        if (is_numeric($key)) {                                                                 // For Floors
            $readFloorDetail =
                [
                    'floorNo' => $this->_floors[$key]['floorNo'],
                    'useType' => $this->_floors[$key]['useType'],
                    'constructionType' => $this->_floors[$key]['constructionType'],
                    'buildupArea' => $this->_floors[$key]['buildupArea'],
                    'dateFrom' => $this->_floors[$key]['dateFrom'],
                    'dateTo' => $this->_floors[$key]['dateUpto']
                ];
        }

        // is implimented rule set 1 (before 2016-2017), (2016-2017 TO 2021-2022), (2021-2022 TO TILL NOW)
        if ($dateFrom < $this->_effectiveDateRule2) {
            $quarterDueDate = calculateQuaterDueDate($dateFrom);
            $onePercPenalty = $this->onePercPenalty($quarterDueDate);                   // One Percent Penalty
            $ruleSets[] = [
                "quarterYear" => calculateFyear($dateFrom),                              // Calculate Financial Year means to Calculate the FinancialYear
                "ruleSet" => "RuleSet1",
                "qtr" => calculateQtr($dateFrom),                                        // Calculate Quarter from the date
                "dueDate" => $quarterDueDate                                             // Calculate Quarter Due Date of the Date
            ];
            $tax = $this->calculateRuleSet1($key, $onePercPenalty);                      // Tax Calculation
            $ruleSetsWithTaxes = array_merge($readFloorDetail, $ruleSets[0], $tax);
            return $ruleSetsWithTaxes;
        }
        // is implimented rule set 2 (2016-2017 TO 2021-2022), (2021-2022 TO TILL NOW)
        if ($dateFrom < $this->_effectiveDateRule3) {
            $quarterDueDate = calculateQuaterDueDate($dateFrom);
            $onePercPenalty = $this->onePercPenalty($quarterDueDate);                   // One Percent Penalty
            $ruleSets[] = [
                "quarterYear" => calculateFyear($dateFrom),
                "ruleSet" => "RuleSet2",
                "qtr" => calculateQtr($dateFrom),
                "dueDate" => $quarterDueDate
            ];
            $tax = $this->calculateRuleSet2($key, $onePercPenalty);
            $ruleSetsWithTaxes = array_merge($readFloorDetail, $ruleSets[0], $tax);
            return $ruleSetsWithTaxes;
        }

        // is implimented rule set 3 (2021-2022 TO TILL NOW)
        if ($dateFrom >= $this->_effectiveDateRule3) {
            $quarterDueDate = calculateQuaterDueDate($dateFrom);                        // One Percent Penalty
            $onePercPenalty = $this->onePercPenalty($quarterDueDate);
            $ruleSets[] = [
                "quarterYear" => calculateFyear($dateFrom),
                "ruleSet" => "RuleSet3",
                "qtr" => calculateQtr($dateFrom),
                "dueDate" => calculateQuaterDueDate($dateFrom)
            ];
            $tax = $this->calculateRuleSet3($key, $onePercPenalty);
            $ruleSetsWithTaxes = array_merge($readFloorDetail, $ruleSets[0], $tax);
            return $ruleSetsWithTaxes;
        }
    }

    /**
     * | One Perc Penalty(1.2.1.1)
     * | @param quarterDueDate Floor Quaterly Due Date
     */
    public function onePercPenalty($quarterDueDate)
    {
        // One Perc Penalty
        $quarterDueDate = Carbon::parse($quarterDueDate)->floorMonth();
        $diffInMonths = $quarterDueDate->diffInMonths($this->_currentQuarterDate);
        if ($quarterDueDate >= $this->_currentQuarterDueDate)                                       // Means the quarter due date is on current quarter or next quarter
            $onePercPenalty = 0;
        else
            $onePercPenalty = $diffInMonths;
        return $onePercPenalty;
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
     * | $arv=$tempArv-$arv;
     * | $latrineTax = ($arv * 7.5) / 100;
     * | $waterTax = ($arv * 7.5) / 100;
     * | $healthTax = ($arv * 6.25) / 100;
     * | $educationTax = ($arv * 5.0) / 100;
     * | $rwhPenalty = 0;
     * | $totalTax = $holdingTax + $latrineTax + $waterTax + $healthTax + $educationTax + $rwhPenalty;
     * | @return Tax totalTax/4 (Quaterly)
     * | Query RunTime=1
     */
    public function calculateRuleSet1($key, $onePercPenalty)
    {
        $readBuildupArea =  $this->_floors[$key]['buildupArea'];
        $readFloorInstallationDate =  $this->_floors[$key]['dateFrom'];
        $readUsageType = $this->_floors[$key]['useType'];
        $readOccupancyType = $this->_floors[$key]['occupancyType'];
        $readPropertyType = $this->_propertyDetails['propertyType'];

        $readRentalValue = collect($this->_rentalValue)->where('usage_types_id', $readUsageType)
            ->where('construction_types_id', $this->_floors[$key]['constructionType'])
            ->first();

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
        $arv = $tempArv - $arv;

        $holdingTax = ($arv * 12.5) / 100;
        $latrineTax = ($arv * 7.5) / 100;
        $waterTax = ($arv * 7.5) / 100;
        $healthTax = ($arv * 6.25) / 100;
        $educationTax = ($arv * 5.0) / 100;
        $rwhPenalty = 0;

        $totalTax = $holdingTax + $latrineTax + $waterTax + $healthTax + $educationTax;
        $onePercPenaltyTax = ($totalTax * $onePercPenalty) / 100;

        // Tax Calculation Quaterly
        $tax = [
            "arv" => roundFigure($arv / 4),
            "rebatePerc" => $arvCalcPercFactor,
            "rentalValue" => $readRentalValue->rate,
            "holdingTax" => roundFigure($holdingTax / 4),
            "latrineTax" => roundFigure($latrineTax / 4),
            "waterTax" => roundFigure($waterTax / 4),
            "healthTax" => roundFigure($healthTax / 4),
            "educationTax" => roundFigure($educationTax / 4),
            "rwhPenalty" => roundFigure($rwhPenalty),
            "totalTax" => roundFigure($totalTax / 4),
            "onePercPenalty" => $onePercPenalty,
            "onePercPenaltyTax" => roundFigure($onePercPenaltyTax / 4)
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
    public function calculateRuleSet2($key, $onePercPenalty)
    {
        $paramRentalRate = $this->_paramRentalRate;
        // Vacant Land RuleSet2
        if ($key == "vacantLand") {
            $plotArea = $this->_propertyDetails['areaOfPlot'];
            $roadTypeId = $this->_readRoadType[$this->_effectiveDateRule2];
            if ($roadTypeId == 4)                                                // i.e. No Road
                $area = decimalToAcre($plotArea);
            else
                $area = decimalToSqMt($plotArea);

            $rentalRate = collect($this->_vacantRentalRates)->where('prop_road_type_id', $this->_readRoadType[$this->_effectiveDateRule2])
                ->where('ulb_type_id', 1)
                ->where('effective_date', $this->_effectiveDateRule2)
                ->first();

            $rentalRate = $rentalRate->rate;
            $occupancyFactor = 1;
            $tax = $area * $rentalRate * $occupancyFactor;

            $onePercPenaltyTax = ($tax * $onePercPenalty) / 100;                                // One Perc Penalty Tax
            $taxQuaterly = [
                "area" => roundFigure($area),
                "rentalRate" => $rentalRate,
                "occupancyFactor" => $occupancyFactor,
                "onePercPenalty"   => $onePercPenalty,
                "totalTax" => roundFigure($tax / 4),
                "onePercPenaltyTax" => roundFigure($onePercPenaltyTax / 4)
            ];
            return $taxQuaterly;
        }

        // Mobile Tower, Hoarding Board, Petrol Pump
        if ($key == "mobileTower" || $key == "hoardingBoard" || $key == "petrolPump") {
            switch ($key) {
                case "mobileTower";
                    $carpetArea = $this->_mobileTowerArea;
                    break;
                case "hoardingBoard":
                    $carpetArea = $this->_hoardingBoard['area'];
                    break;
                case "petrolPump":
                    $carpetArea = $this->_petrolPump['area'];
                    break;
            }

            $readMultiFactor = collect($this->_multiFactors)->where('usage_type_id', 45)
                ->where('effective_date', $this->_effectiveDateRule2)
                ->first();
            $multiFactor = (float)$readMultiFactor->multi_factor;

            $paramOccupancyFactor = 1.5;
            // Rental Rate Calculation
            $rentalRates = collect($this->_rentalRates)
                ->where('prop_road_type_id', $this->_readRoadType[$this->_effectiveDateRule2])
                ->where('construction_types_id', 1)
                ->where('effective_date', $this->_effectiveDateRule2)
                ->first();
            $rentalRate = $rentalRates->rate * $paramRentalRate;
        }

        if (is_numeric($key)) {                                                             // Applicable For Floors
            $readFloorUsageType = $this->_floors[$key]['useType'];
            $readFloorBuildupArea = $this->_floors[$key]['buildupArea'];
            $readFloorOccupancyType = $this->_floors[$key]['occupancyType'];
            $paramCarpetAreaPerc = ($readFloorUsageType == 1) ? 70 : 80;
            $paramOccupancyFactor = ($readFloorOccupancyType == 2) ? 1 : 1.5;

            $readMultiFactor = collect($this->_multiFactors)->where('usage_type_id', $readFloorUsageType)
                ->where('effective_date', $this->_effectiveDateRule2)
                ->first();
            $multiFactor = (float)$readMultiFactor->multi_factor;

            $carpetArea = ($readFloorBuildupArea * $paramCarpetAreaPerc) / 100;

            // Rental Rate Calculation
            $rentalRates = collect($this->_rentalRates)
                ->where('prop_road_type_id', $this->_readRoadType[$this->_effectiveDateRule2])
                ->where('construction_types_id', $this->_floors[$key]['constructionType'])
                ->where('effective_date', $this->_effectiveDateRule2)
                ->first();
            $rentalRate = $rentalRates->rate * $paramRentalRate;
        }

        $rwhPenalty = 0;

        $tempArv = $carpetArea * $multiFactor * $paramOccupancyFactor * (float)$rentalRate;
        $arv = ($tempArv * 2) / 100;

        // Rain Water Harvesting Penalty If The Plot Area is Greater than 3228 sqft. and Rain Water Harvesting is none
        if ($this->_rwhPenaltyStatus == true) {
            $rwhPenalty = $arv / 2;
        }

        $totalTax = $arv + $rwhPenalty;
        $onePercPenaltyTax = ($totalTax * $onePercPenalty) / 100;
        // All Taxes Quaterly
        $tax = [
            "arv" => roundFigure($arv / 4),
            "carpetArea" => $carpetArea,
            "multiFactor" => $multiFactor,
            "rentalRate" => roundFigure($rentalRate),
            "occupancyFactor" => $paramOccupancyFactor,

            "holdingTax" => 0,
            "latrineTax" => 0,
            "waterTax" => 0,
            "healthTax" => 0,
            "educationTax" => 0,

            "rwhPenalty" => roundFigure($rwhPenalty / 4),
            "totalTax" => roundFigure($totalTax / 4),
            "onePercPenalty" => $onePercPenalty,
            "onePercPenaltyTax" => roundFigure($onePercPenaltyTax / 4)
        ];
        return $tax;
    }

    /**
     * | RuleSet 3 Calculation (1.2.1.3)
     * | @param key arrayKey value
     */
    public function calculateRuleSet3($key, $onePercPenalty)
    {
        // Vacant Land RuleSet3
        if ($key == "vacantLand") {
            $plotArea = $this->_propertyDetails['areaOfPlot'];
            $roadTypeId = $this->_readRoadType[$this->_effectiveDateRule3];
            if ($roadTypeId == 4)                                                // i.e. No Road
                $area = decimalToAcre($plotArea);
            else
                $area = decimalToSqMt($plotArea);
            $rentalRate = collect($this->_vacantRentalRates)->where('prop_road_type_id', $this->_readRoadType[$this->_effectiveDateRule3])
                ->where('ulb_type_id', 1)
                ->where('effective_date', $this->_effectiveDateRule3)
                ->first();

            $rentalRate = $rentalRate->rate;
            $occupancyFactor = 1;
            $tax = (float)$area * $rentalRate * $occupancyFactor;
            $onePercPenaltyTax = ($tax * $onePercPenalty) / 100;
            $taxQuaterly = [
                "area" => roundFigure($area),
                "rentalRate" => $rentalRate,
                "occupancyFactor" => $occupancyFactor,
                "onePercPenalty"   => $onePercPenalty,
                "totalTax" => roundFigure($tax / 4),
                "onePercPenaltyTax" => roundFigure($onePercPenaltyTax / 4)
            ];
            return $taxQuaterly;
        }

        // For Mobile Tower, Hoarding Board, Petrol Pump
        if ($key == "mobileTower" || $key == "hoardingBoard" || $key == "petrolPump") {
            $readCircleRate = $this->_capitalValueRateMPH;
            switch ($key) {
                case "mobileTower";
                    $readBuildupArea = $this->_mobileTowerArea;
                    break;
                case "hoardingBoard":
                    $readBuildupArea = $this->_hoardingBoard['area'];
                    break;
                case "petrolPump":
                    $readBuildupArea = $this->_petrolPump['area'];
                    break;
            }

            $paramOccupancyFactor = 1.5;
            $taxPerc = 0.15;
            $readMultiFactor = collect($this->_multiFactors)->where('usage_type_id', 45)
                ->where('effective_date', $this->_effectiveDateRule3)
                ->first();
            $readCalculationFactor = $readMultiFactor->multi_factor;

            $rentalRates = collect($this->_rentalRates)
                ->where('prop_road_type_id', $this->_readRoadType[$this->_effectiveDateRule3])
                ->where('construction_types_id', 1)
                ->where('effective_date', $this->_effectiveDateRule3)
                ->first();
            $readMatrixFactor = $rentalRates->rate;
        }

        // For Floors
        if (is_numeric($key)) {                                                                            // Applicable for floors
            $readCircleRate = $this->_capitalValueRate[$key];
            $readFloorUsageType = $this->_floors[$key]['useType'];
            $readBuildupArea = $this->_floors[$key]['buildupArea'];

            $readFloorOccupancyType = $this->_floors[$key]['occupancyType'];
            $paramOccupancyFactor = ($readFloorOccupancyType == 2) ? 1 : 1.5;

            $readUsageType = $this->_floors[$key]['useType'];
            $taxPerc = ($readUsageType == 1) ? 0.075 : 0.15;                                                // 0.075 for Residential and 0.15 for Commercial

            $readMultiFactor = collect($this->_multiFactors)->where('usage_type_id', $readFloorUsageType)
                ->where('effective_date', $this->_effectiveDateRule3)
                ->first();

            $readCalculationFactor = $readMultiFactor->multi_factor;                                        // (Calculation Factor as Multi Factor)
            $rentalRates = collect($this->_rentalRates)
                ->where('prop_road_type_id', $this->_readRoadType[$this->_effectiveDateRule3])
                ->where('construction_types_id', $this->_floors[$key]['constructionType'])
                ->where('effective_date', $this->_effectiveDateRule3)
                ->first();
            $readMatrixFactor = $rentalRates->rate;                                                         // (Matrix Factor as Rental Rate)
        }

        $calculatePropertyTax = ($readCircleRate * $readBuildupArea * $paramOccupancyFactor * $taxPerc * (float)$readCalculationFactor) / 100;
        $calculatePropertyTax = $calculatePropertyTax * $readMatrixFactor;
        $rwhPenalty = 0;
        if ($this->_rwhPenaltyStatus == true) {
            $rwhPenalty = $calculatePropertyTax / 2;
        }

        $totalTax = $calculatePropertyTax + $rwhPenalty;
        $onePercPenaltyTax = ($totalTax * $onePercPenalty) / 100;                                           // One Percent Penalty

        // Tax Calculation Quaterly
        $tax = [
            "arv" => roundFigure($calculatePropertyTax / 4),
            "circleRate" => $readCircleRate,
            "buildupArea" => $readBuildupArea,
            "occupancyFactor" => $paramOccupancyFactor,
            "taxPerc" => $taxPerc,
            "calculationFactor" => $readCalculationFactor,
            "matrixFactor" => $readMatrixFactor,

            "holdingTax" => 0,
            "latrineTax" => 0,
            "waterTax" => 0,
            "healthTax" => 0,
            "educationTax" => 0,

            "rwhPenalty" => roundFigure($rwhPenalty / 4),
            "totalTax" => roundFigure($totalTax / 4),
            "onePercPenalty" => $onePercPenalty,
            "onePercPenaltyTax" => roundFigure($onePercPenaltyTax / 4)
        ];
        return $tax;
    }

    /**
     * | Total Final Payable Amount (1.6)
     * | @var demand Sum Collection of TotalTax and TotalOnePercPenalty
     * | @var fine LateAssessment Fine (5000 For Commercial, 2000 For Residential)
     */
    public function calculateFinalPayableAmount()
    {
        $demand = collect($this->_GRID['details'])->pipe(function ($values) {
            return collect([
                'totalTax' => roundFigure($values->sum('totalTax')),
                'totalOnePercPenalty' => roundFigure($values->sum('onePercPenaltyTax'))
            ]);
        });

        $this->_GRID['demand'] = $demand;
        $this->_GRID['demand']['isResidential'] = $this->_isResidential;

        $fine = 0;
        // Check Late Assessment Penalty for Building
        if ($this->_propertyDetails['propertyType'] != $this->_vacantPropertyTypeId) {
            $floorDetails = collect($this->_floors);
            $lateAssementFloors = $floorDetails->filter(function ($value, $key) {                           // Collection of floors which have late Assessment
                $currentDate = Carbon::now()->floorMonth();
                $dateFrom = Carbon::createFromFormat('Y-m-d', $value['dateFrom'])->floorMonth();
                $diffInMonths = $currentDate->diffInMonths($dateFrom);
                return $diffInMonths > 3;
            });
            $lateAssessmentStatus = $lateAssementFloors->isEmpty() == true ? false : true;

            // Late Assessment Penalty
            if ($lateAssessmentStatus == true) {
                $fine = $this->_isResidential == true ? 2000 : 5000;
            }
        }

        // Check Late Assessment Penalty for Vacant Land
        if ($this->_propertyDetails['propertyType'] == $this->_vacantPropertyTypeId) {
            $currentDate = Carbon::now()->floorMonth();
            $dateFrom = Carbon::createFromFormat('Y-m-d', $this->_propertyDetails['dateOfPurchase'])->floorMonth();
            $diffInMonths = $currentDate->diffInMonths($dateFrom);
            $lateAssessmentStatus = $diffInMonths > 3 ? true : false;
            if ($lateAssessmentStatus == true) {
                $fine = $this->_isResidential == true ? 2000 : 5000;
            }
        }

        $this->_GRID['demand']['lateAssessmentStatus'] = $lateAssessmentStatus;
        $this->_GRID['demand']['lateAssessmentPenalty'] = $fine;

        $taxes = collect($this->_GRID['demand'])->only(['totalTax', 'totalOnePercPenalty', 'lateAssessmentPenalty']);
        $totalDemandAmount = $taxes->sum();                                                                             // Total Demand with Penalty
        $this->_GRID['demand']['totalDemand'] = roundFigure($totalDemandAmount);
        $this->_GRID['demand']['rebatePerc'] = $this->readRebate();
        $this->_GRID['demand']['payableAmount'] = $this->calculatePayableAmount();
    }

    /**
     * | Total Rebate Percent(1.6.1)
     * | @var ownerDetails the first Prefered owner to be given the discount rebate
     * | @var rebate the Rebate Percentage
     */
    public function readRebate()
    {
        $ownerDetails = collect($this->_propertyDetails['owner'])->first();
        $rebate = 0;
        if ($this->_loggedInUserType == 'Citizen') {                                                // In Case of Citizen
            $rebate += 5;
        }
        if ($this->_loggedInUserType == 'JSK') {                                                    // In Case of JSK
            $rebate += 2.5;
        }

        if ($ownerDetails['isArmedForce'] == 1 || $ownerDetails['isSpeciallyAbled'] == 1 || $ownerDetails['gender']  > 1) {
            $rebate += 5;
        }
        return $rebate;
    }

    /**
     * | Final Payable Amount
     * --------------------- Initialization ------------------
     * | @var totalDemand generated total demand with all the penalties 
     * | @var rebatePerc rebate Percent to be given to the user
     * ----------------- Calculation -------------------------
     * | $rebateAmount = ($totalDemand * $rebatePerc) / 100;
     * | $payableAmount = $totalDemand - $rebateAmount;
     * @return payableAmount Final Payable Amount of the User
     */
    public function calculatePayableAmount()
    {
        $totalDemand = $this->_GRID['demand']['totalDemand'];
        $rebatePerc = $this->_GRID['demand']['rebatePerc'];

        $rebateAmount = ($totalDemand * $rebatePerc) / 100;
        $payableAmount = $totalDemand - $rebateAmount;
        return roundFigure($payableAmount);
    }

    /**
     * | Summary Preview for The Saf Tax Calculation
     */
    public function summarySafCalculation()
    {
        $propertyTypeId = $this->_propertyDetails['propertyType'];                                      // i.e Property Type Building
        if ($propertyTypeId != $this->_vacantPropertyTypeId) {
            $ruleSets = [
                "Annual Rental Value - As Per Old Rule (Effect Upto 31-03-2016)" => [                   // RuleSet1
                    "Annual Rental Value(ARV)" => "BuiltUpArea x Rental value",
                    "After calculating the A.R.V. the rebates are allowed in following manner :-" =>
                    [
                        "Holding older than 25 years (as on 1967-68)" => "10% Own occupation",
                        "Residential" => "30%",
                        "Commercial" => "15%"
                    ],
                    "Tax at the following rates are imposed on the claculated ARV as per old rule" => [
                        "Holding tax" => "12.5%",
                        "Latrine tax" => "7.5%",
                        "Water tax" => "7.5%",
                        "Health cess" => "6.25%",
                        "Education cess" => "5.0%"
                    ],
                    "Calculated Quaterly Tax" => "(Yearly Tax) ÷ 4"
                ],
                "Annual Rental Value - As ARV Rule (Effect From 01-04-2016 to 31-03-2022)" => [          // RuleSet2
                    "Carpet Area" => [
                        "Residential" => "70% of Builtup Area",
                        "Commercial" => "80% Of Builtup Area"
                    ],
                    "Annual Rental Value (ARV)" => "Carpet Area X Usage Factor X Occupancy Factor X Rental Rate",
                    "Total Quarterly Tax Details" => "((ARV X 2%) ÷ 4)"
                ],
                "Capital Value - As Per Current Rule (Effect From 01-04-2022)" => [                      // RuleSet 3
                    "Tax Percentage" => [
                        "Residential" => 0.075,
                        "Commercial" => 0.150,
                        "Commercial & greater than 25000 sqft" => 0.20
                    ],
                    "Property Tax" => "Circle Rate X Buildup Area X Occupancy Factor X Tax Percentage X Calculation Factor X Matrix Factor Rate (Only in case of 100% residential property)"
                ]
            ];
        }

        if ($propertyTypeId == $this->_vacantPropertyTypeId) {                              // i.e Property Type is Vacant Land
            $ruleSets = [
                "Tax - As Per Old Rule (Effect From 01-04-2016 to 31-03-2022)" => [         // Rule 1
                    "Tax" => "Area (sqmt) X Rental Rate X Occupancy Factor",
                    "taxes calculated on quarterly basis" => "Yearly Tax ÷ 4"
                ],
                "Tax - As Per Current Rule (Effect From 01-04-2022)" => [                   // Rule 2
                    "Tax" => " Area (sqm) X Rental Rate X Occupancy Factor",
                    "Taxes calculated on quarterly basis" => "(Yearly Tax ÷ 4)"
                ]

            ];
        }
        return $ruleSets;
    }
}
