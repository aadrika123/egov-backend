<?php

namespace App\EloquentClass\Property;

use App\Models\PropParamUsageTypeMultFactor;
use App\Models\PropParamVacantRentalRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Exception;
use Illuminate\Support\Facades\Config;

use App\Traits\Auth;
use Carbon\Carbon;

class SafCalculation
{
    use Auth;   //Trate use 
    /**
     * created by sandeep bara 
     * date 01-09-2022
     * private folder not editable befor edit Please conforme Not Effecte Other Method 
     */
    protected $Tax;
    protected array $Rulsets;
    protected array $FYearQuater;
    public array $TotalTax;
    public function __construct()
    {
        $this->_Rulsets = [
            "building" => ["buildingRulSet1", "buildingRulSet12", "buildingRulSet3"],
            "vacantLand" => ["vacantRulSet1", "vacantRulSet2"],
        ];
    }
    public function getAllVacantLandRentalRate(): array // stdcl object array
    {
        /**
         * Description -> This Function Retrive All Vacant Land Rental Value From Data-Base And Store in Redis
         * ===========Tables ===================
         *                                      | id
         * 1. prop_param_vacant_rental_rates    | rate
         *                                      | ulb_type_id (1 ->  Municipal Corporations, 2-> Nagar Parishad, 3->Nagar Panchayat)
         * 
         *                                      | range_from_sqft
         * 2. prop_param_road_types             | range_upto_sqft
         *                                      | effective_date
         * 
         * 3. prop_road_type_masters            | road_type
         * 
         * =============Dependent Function =========
         * 1. getRodeType() -> getRodeType()
         * 
         * =============Function Use ===============
         * 1. AllVacantLandRentalRateSet() -> for Store the data from data-base to Redis
         * 
         * ==============Variable Used =============
         * $redis           -> Redis Object;
         * $rentalVal       -> Redis::get("AllVacantLandRentalRate") | Case 1.) If Data Not Avalable On Redis Retive From Data-Base And Stroe in Redis As Well As In The Variable
         *                                                           | Case 2.) If Data Is Avalable On Redis Then Retive And Store In Variable
         * 
         * ==============Reasponse=================
         * retrun StdClass Object Array
         * ==============Request===================
         * 
         */
        $redis = Redis::connection(); //Redis::del("AllVacantLandRentalRate");
        $rentalVal = json_decode(Redis::get("AllVacantLandRentalRate")) ?? null;
        if (!$rentalVal) {
            $rentalVal = DB::select("select prop_road_type_masters.id,road_type,range_from_sqft,range_upto_sqft,prop_param_road_types.effective_date,
                                        rate,ulb_type_id
                                    from prop_param_vacant_rental_rates
                                    join prop_param_road_types on prop_param_vacant_rental_rates.prop_road_typ_id=prop_param_road_types.prop_road_typ_id
                                        and prop_param_vacant_rental_rates.effective_date = prop_param_road_types.effective_date
                                    join prop_road_type_masters on prop_param_road_types.prop_road_typ_id = prop_road_type_masters.id");

            $this->AllVacantLandRentalRateSet($redis, $rentalVal);
        }
        return  $rentalVal;
    }

    public function getRodeType(float $with_in_arear_sft,  $effective_date, int $ulb_type_id): array //stdcl object array
    {
        /**
         * Description -> This function Do Filteration Using Retiving Stdclass Objecte Array
         * ================ Super Function ===================
         * 1. getAllVacantLandRentalRate()
         * 
         * ================ Dependent Function ===============
         * 1. RentalRate()
         * 
         * ================ Requst ===========================
         * 1. $with_in_arear_sft        -> Road Width in SquereFeet
         * 2. $effective_date           -> Date Effective | case 1.) 2016-04-01 For Old Rule
         *                                                | case 2.) 2022-04-01 For New Rule
         * 
         * 3. $ulb_type_id              -> Ulb Type Id    |(1 ->  Municipal Corporations, 2-> Nagar Parishad, 3->Nagar Panchayat)
         * 
         * ====================Response =======================
         * Stb Class Object Array
         * 
         * ====================Helper Function ================
         * 1. floatRound() -> Fore Round Floating Point Value Upto 2 Decimal Point
         * 
         * ================Variables ================================
         * $vacantLandRentalRate = getAllVacantLandRentalRate()
         * $with_in_arear_sft    = floatRound($with_in_arear_sft,2)
         * $roadType             = 
         */
        try {
            $vacantLandRentalRate = $this->getAllVacantLandRentalRate();
            $with_in_arear_sft = floatRound($with_in_arear_sft, 2);
            $roadType = array_filter($vacantLandRentalRate, function ($val) use ($with_in_arear_sft, $effective_date, $ulb_type_id) {
                if ($val->ulb_type_id == $ulb_type_id && $val->effective_date == $effective_date && $val->range_from_sqft <= $with_in_arear_sft && ($val->range_upto_sqft ? $val->range_upto_sqft >= $with_in_arear_sft : true)) {

                    return true;
                }
            });
            if (!$roadType) {
                throw new Exception("Road Type Not Found");
            }
            return array_values($roadType);
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }
    public function RentalRate(float $road_width_in_sft, $effective_date, int $ulb_type_id): float
    {
        /**
         * Description -> This Function Return Rental Value For Vacant Land. Do Operation Depending Upton Given StbClass Object Array By  getRodeType()
         * ===================Super Function =========================
         * 1. getRodeType()
         * 
         * ==================Request ================================
         * 1. $with_in_arear_sft        -> Road Width in SquereFeet
         * 2. $effective_date           -> Date Effective | case 1.) 2016-04-01 For Old Rule
         *                                                | case 2.) 2022-04-01 For New Rule
         * 
         * 3. $ulb_type_id              -> Ulb Type Id    |(1 ->  Municipal Corporations, 2-> Nagar Parishad, 3->Nagar Panchayat)
         * 
         * ================Response =================================
         * Rental Rate(Vacand Land)          -> Floating Point Number
         * 
         * ================Variables ================================
         * $road_type = getRodeType($road_width_in_sft, $effective_date, $ulb_type_id)
         */
        try {
            $road_type = $this->getRodeType($road_width_in_sft, $effective_date, $ulb_type_id);
            if (!$road_type) {
                throw new Exception("Road Type Rental Rate Not Found");
            }
            return $road_type[0]->rate ?? 0.0;
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }
    public function getAllOccuPencyFacter(string $usege_type = null): array //stdcls object array
    {
        /**
         * Description -> This Function is Retrive Occupency and Occupency Factor From Data-Base And Store In Redis
         * 
         * ================== Tables =============================
         *                           | id
         * prop_occupency_facters    | occupancy_name
         *                           | mult_factor
         *                           | status
         * 
         * ================= Request =============================
         * $usege_type      ->  defalut Parameter  Case 1.) If Supply Usage Type Then Filter Data And Return Filter Stdcl Object Array
         *                                         Case 2.) If No Sypply Usage Type Then Return All OccupencyFacter
         * 
         * ================= Dependend Function===================
         * 1. OccuPencyFacter()
         * 
         * ================= Response ============================
         * Stdcl Object Array 
         * 
         * ================= Variable ============================
         * $redis               -> Redis Object
         * $OccuPencyFacter     -> Stdcl Object (Store And Return This Variable)
         * 
         */
        try {
            $redis = Redis::connection();
            $OccuPencyFacter = json_decode(Redis::get('OccuPencyFacter')) ?? null;
            if (!$OccuPencyFacter) {
                $OccuPencyFacter = DB::select("select * from prop_occupency_facters where status =1 ");
                $this->OccuPencyFacterSet($redis, $OccuPencyFacter);
            }
            if ($usege_type)
                $OccuPencyFacter = array_filter($OccuPencyFacter, function ($val) use ($usege_type) {
                    return $val->occupancy_name == $usege_type ? true : false;
                });
            if (!$OccuPencyFacter) {
                throw new Exception("Occupency Facters Not Found");
            }
            return  array_values($OccuPencyFacter);
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }

    public function OccuPencyFacter(string $usege_type): float
    {
        /**
         * Description  -> This Function Perform Filteration Depending On Retrive Stdcl Object Array Return Occupency Facter 
         * ==================Super Function ========================
         * 1. getAllOccuPencyFacter($usege_type)
         * 
         * ================= Request ===============================
         * $usege_type          -> TENANTED , SELF OCCUPIED 
         *                                                       | 1   -> Self Occupied
         *                                                       | 1.5 -> Tenented(Rent)
         * 
         * ================ Response ==============================
         * $OccuPencyFacter   ->  Floating Point Number
         * 
         */
        return  $OccuPencyFacter = $this->getAllOccuPencyFacter($usege_type)[0]->mult_factor ?? 0;
    }

    #===================== Core Function =================================
    public function aqrtMeterToFeet(float $num): float
    {
        /**
         * Description -> This Function  Convert squere Meter To squere Feet
         * ============== Request =============================
         * $num         -> squere Meter
         * 
         * ============== Response ============================
         * $num * 10.76391042; 
         */
        return $num * 10.76391042;
    }
    public function aqrtFeetToMeter(float $num): float
    {
        /**
         * Description -> This Function  Convert squere Feet To squere Meter
         * ============== Request =============================
         * $num         -> squere Feet 
         * 
         * ============== Response ============================
         * $num / 10.76391042; 
         */
        return $num / 10.76391042;
    }
    public function DecimalToSqtMeter(float $num)
    {
        /**
         * Description -> This Function  Convert Decimal To squere Meter
         * ============== Request =============================
         * $num         -> Decimal 
         * 
         * ============== Response ============================
         * $num * 40.46485; 
         */
        return ($num * 40.46485);
    }

    # =================== End Core Function ==============================

    public function getAllRentalValue(int $ulb_id): array // array of stdcl
    {
        /**
         * Description  -> This Function  Retrive Data From  Data-Base And Store In Redis 
         *                          Case 1.) If Data Not Found On Redis Then Retrive Data From Data-Base And Store Data In Redis 
         *                          Case 2.) If Data Found On Redis then Retrive Data 
         * 
         * Note :- This Values Is Change According To Ulb
         * 
         * =====================Tables ====================================
         *                              | id
         *                              | usage_types_id        -> prop_param_usage_types ->id   Case 1.) If usage_type = RESIDENTIAL  Then 1;
         *                              |                                                        Case 2.) If usage_type != RESIDENTIAL Then 2;
         *                              | zone_id
         * prop_param_rental_values     | construction_types_id
         *                              | rate
         *                              | ulb_id
         *                              | status
         * 
         * ====================== Request =============================
         * $ulb_id              -> Ubl Id     ->  ulb_masters->id (Table)
         * 
         * =======================Response ============================
         * Stdcl Object Array
         * 
         * ====================== Variables ==========================
         * $redis           ->  Redis Objects
         * $AllRentalValue  -> Stdcl Objects Array
         * 
         * ====================== Dependent Function =================
         * 1. RentalValue()
         * 
         * =====================Function Usage ======================
         * AllRentalValueSet($redis,$ulb_id,$AllRentalValue)  // Trate/Auth.php
         */
        $redis = Redis::connection();
        $AllRentalValue = json_decode(Redis::get("AllRentalValue:$ulb_id")) ?? null;
        if (!$AllRentalValue) {
            $AllRentalValue = DB::select("select * from prop_param_rental_values where status=1");
            $this->AllRentalValueSet($redis, $ulb_id, $AllRentalValue);
        }
        return $AllRentalValue;
    }

    public function RentalValue(int $ulb_id, int $usege_type_id, int $zone_id, int $construction_type_id): float
    {
        /**
         * Description  -> This Function  Filter Stdcl Object Array  From  getAllRentalValue() Depending Upon Pass Data ($usege_type_id,$zone_id,$construction_type_id)
         *                 This Function use On Arv(BuildingRuleSet1) Calculation Of Building Property 
         * 
         * ====================== Super Function ====================
         * getAllRentalValue($ulb_id)
         * 
         * ====================== Requst ============================
         * $ulb_id              ->  Ubl Id     ->  ulb_masters->id (Table)
         * $usege_type_id       ->  prop_param_usage_types ->id   |Case 1.) If usage_type = RESIDENTIAL  Then 1;
         *                                                        |Case 2.) If usage_type != RESIDENTIAL Then 2;
         * $zone_id             ->  | 1-> Zone1
         *                          | 2-> Zone2
         * $construction_type_id->  prop_param_construction_types->id (Table) 
         * 
         * ======================= Response =========================
         *  prop_param_rental_values->rate(Table)    
         * 
         * ======================= Variables =======================
         *  $AllRentalValue         -> Stdcl Objection (prop_param_rental_values)
         *  $RentalValue            -> Stdcl Objection (prop_param_rental_values) Filter Data       
         */
        try {
            $AllRentalValue = $this->getAllRentalValue($ulb_id);
            $RentalValue = array_filter($AllRentalValue, function ($val) use ($usege_type_id, $zone_id, $construction_type_id) {
                if ($val->usage_types_id == $usege_type_id && $val->zone_id == $zone_id && $val->construction_types_id == $construction_type_id)
                    return true;
            });
            if (!$RentalValue) {
                throw new Exception("ARV Rental Rate Not Found");
            }
            return array_values($RentalValue)[0]->rate ?? 0.0;
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }

    public function getAllBuildingUsageFacter(string $UsageType = null): array //stdcl object
    {
        /**
         * Description  -> This Function Retrive UsageFacter From Data-Base And Store In Redis And Next Time Use That Redis Data
         * 
         * Note :- If Parameter Supply then Return Filter Data
         * 
         * ======================= Tables ============================
         *                                  | id
         *  prop_param_usage_types          | mult_factor
         *                                  | effective_date
         * 
         * prop_param_usage_types           | usage_type
         * 
         * ==================== Depending Function ===================
         * UsageFacter()
         * 
         * ==================== Function Usage =======================
         * AllBuildingUsageFacterSet($redis,$AllBuildingUsageFacter)    # Trate/Auth.php
         * 
         * ===================== Request =============================
         * $UsageType   -> optional parameter  -> prop_param_usage_types->usage_type (Table)
         * 
         * ==================== Response ============================
         * $AllBuildingUsageFacter -> Stdcl Object Array
         */
        $redis = Redis::connection();
        $AllBuildingUsageFacter = json_decode(Redis::get("AllBuildingUsageFacter")) ?? null;
        if (!$AllBuildingUsageFacter) {
            $AllBuildingUsageFacter = DB::select("SELECT prop_param_usage_types.id,usage_type,mult_factor,effective_date 
                                                  FROM prop_param_usage_type_mult_factors 
                                                  JOIN prop_param_usage_types ON prop_param_usage_types.id = prop_param_usage_type_mult_factors.prop_param_usage_types_id
                                                        AND prop_param_usage_types.status =1
                                                  where prop_param_usage_type_mult_factors.status =1 
                                                  ORDER BY prop_param_usage_type_mult_factors.effective_date ");
            $this->AllBuildingUsageFacterSet($redis, $AllBuildingUsageFacter);
        }
        if ($UsageType)
            $AllBuildingUsageFacter = array_filter($AllBuildingUsageFacter, function ($val) use ($UsageType) {
                return $val->usage_type == $UsageType ? true : false;
            });
        return  array_values($AllBuildingUsageFacter);
    }

    public function UsageFacter(int $UsageTypeID, $effectiveDate): float
    {
        /**
         * Description -> This Function  Filter The Data According To Supply perameter ($UsageTypeID,$effectiveDate)
         * Note :- This function use ARV(BuildingRuleSet2)
         * =================Super Function =======================
         * getAllBuildingUsageFacter()
         * 
         * ================ Request ==============================
         * $UsageTypeID         -> prop_param_usage_types->id (Table)
         * $effectiveDate       -> prop_param_usage_type_mult_factors->effective_date (Table)
         * 
         * =============== Response ==============================
         * prop_param_usage_type_mult_factors->mult_factor (floatingpoint)
         */
        try {
            $AllBuildingUsageFacter = $this->getAllBuildingUsageFacter();
            $AllBuildingUsageFacter = array_filter($AllBuildingUsageFacter, function ($val) use ($UsageTypeID, $effectiveDate) {
                return $val->id == $UsageTypeID && $val->effective_date == $effectiveDate ? true : false;
            });
            $AllBuildingUsageFacter = array_values($AllBuildingUsageFacter);
            if (!$AllBuildingUsageFacter) {
                throw new Exception("Usage Facter Not Found");
            }
            return $AllBuildingUsageFacter[0]->mult_factor ?? 0.0;
        } catch (Exception $e) {
            echo $e->getMessage();
            die($UsageTypeID);
        }
    }

    public function getAllBuildingRentalValue(int $ulb_id): array //stdcl object array
    {
        /**
         * Description -> This Function Retive Rental Rate From DataBase For Building And Store Data in Redis
         * 
         * Note :- This Value Is Change According To Ulb Wise
         * 
         * ================= Tables ===============================
         *                                          | prop_road_typ_id      ->   prop_param_road_types->id(Table)
         *                                          | construction_types_id
         * prop_param_building_rental_rates         | rate
         *                                          | effective_date
         * 
         * prop_param_building_rental_consts        | ulb_id
         *                                          | x
         * 
         * ===================Dependent Function ================
         * BuildingRentalValue()
         * 
         * ===================Function Used ======================
         * AllBuildingRentalValueSet($redis,$ulb_id,$AllBuildingRentalValue)  -> For Set Data In Redis 
         *                                                                       Path (Traits/Auth.php)
         * 
         * ================= Request ============================
         * $ulb_id    -> ulb_ward_masters->id (Table)
         * 
         * ================ Response ============================
         * Stdcl object Array 
         * 
         * =============== Variables ============================
         * $redis                   -> Redis object
         * $AllBuildingRentalValue  -> Stdcl Object
         * 
         * 
         */
        $redis = Redis::connection();
        $AllBuildingRentalValue = json_decode(Redis::get("AllBuildingRentalValue:$ulb_id")) ?? null;
        if (!$AllBuildingRentalValue) {
            $AllBuildingRentalValue = DB::select("SELECT prop_road_typ_id,construction_types_id,
                                                    ulb_id,x, rate,prop_param_building_rental_consts.effective_date 
                                                  FROM prop_param_building_rental_rates 
                                                  JOIN prop_param_building_rental_consts 
                                                        ON prop_param_building_rental_consts.effective_date = prop_param_building_rental_rates.effective_date                                                        
                                                  where prop_param_building_rental_rates.status = 1 
                                                        AND  prop_param_building_rental_consts.status =1 
                                                        AND prop_param_building_rental_consts.ulb_id = $ulb_id
                                                  ORDER BY prop_param_building_rental_consts.effective_date ");
            $this->AllBuildingRentalValueSet($redis, $ulb_id, $AllBuildingRentalValue);
        }
        return  array_values($AllBuildingRentalValue);
    }

    public function BuildingRentalValue(int $ulb_id, int $RoadTypeId, int $constructionTypeID, $effectiveDate): float
    {
        /**
         * Description ->  This Function Filter Data(Stdcl Object) Recived From getAllBuildingRentalValue() And Find The Rental Rate
         * 
         * ===================== Supper Function =========================
         * getAllBuildingRentalValue($ulb_id);
         * 
         * ===================== Request =================================
         * $ulb_id                 ->  ulb_ward_masters->id (Table)
         * $RoadTypeId             ->  prop_param_road_types->id (Table)
         * $constructionTypeID     -> prop_param_occupancy_types->id (Table)
         * $effectiveDate          -> prop_param_building_rental_rates->effective_date  (Table)
         * 
         * =================== Response =================================
         * Rental Rate  = prop_param_building_rental_rates->rate  x prop_param_building_rental_consts->x;
         * 
         * =================== Variable =================================
         * $AllBuildingUsageFacter  -> StbCl Object Array
         * 
         */
        try {
            $AllBuildingUsageFacter = $this->getAllBuildingRentalValue($ulb_id);

            $AllBuildingUsageFacter = array_filter($AllBuildingUsageFacter, function ($val) use ($RoadTypeId, $constructionTypeID, $effectiveDate) {
                return ($val->prop_road_typ_id == $RoadTypeId && $val->construction_types_id == $constructionTypeID && $val->effective_date == $effectiveDate) ? true : false;
            });
            $AllBuildingUsageFacter = array_values($AllBuildingUsageFacter);
            if (!$AllBuildingUsageFacter) {
                throw new Exception("Building Rental Value Not Found");
            }
            return $AllBuildingUsageFacter[0]->rate * $AllBuildingUsageFacter[0]->x ?? 0.0;
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }

    public function getAllCircleRate(int $ulb_id): array //Stdcl object array
    {
        $redis = Redis::connection(); //Redis::del("AllCircleRate:$ulb_id");
        $CircleRate = json_decode(Redis::get("AllCircleRate:$ulb_id")) ?? null;
        if (!$CircleRate) {
            $CircleRate = DB::select("SELECT *
                                    FROM prop_param_capital_value_rate_raw
                                    WHERE ulb_id = $ulb_id");
            $this->AllCircleRateSet($redis, $ulb_id, $CircleRate);
        }
        return  $CircleRate;
    }
    public function CircleRate(int $ulb_id, string $ward_no, string $columnName): float
    {
        try {
            $AllCircleRate = $this->getAllCircleRate($ulb_id);
            $CircleRate = array_filter($AllCircleRate, function ($val) use ($ward_no) {
                return $val->ward_no == $ward_no;
            });
            $CircleRate = array_values($CircleRate);

            if (!$CircleRate) {
                throw new Exception("Circle Rate Note Faound");
            }
            return $CircleRate[0]->$columnName;
        } catch (Exception  $e) {
            echo $e->getMessage();
            die;
        }
    }

    # ===================RuleSet Start ===================================
    /**
     * ==============OCCUPANCY FACTER ==================
     * SELF     -> 1
     * TENATED  -> 1.5 
     * 
     * ============== Ulb Type ========================
     * 1    ->  Municipal Corporations
     * 2    ->  Nagar Parishad 
     * 3    ->  Nagar Panchayat
     * 
     * =============RENTAL RATE ========================
     * 
     */
    public function vacantRulSet1(float $road_width_in_sft, float $area_in_dml, int $ulb_type_id, string $usege_type): array
    {
        /**
         * Description  -> This Rule Is Applicable For Vacant Land 
         * Validity     -> 2016-04-01 to 2022-03-31 (2016-2017 to 2021-2022)
         * =============== Formula==================================
         * ------------------------------------------------------
         * | Tax = Area(sqmt) X Rental Rate X Occupancy Facter  |
         * ------------------------------------------------------
         * Area             = DecimalToSqtMeter($area_in_dml)
         * Rental Rate      = RentalRate($road_width_in_sft,"2016-04-01",$ulb_type_id)
         * Occupancy Facter = OccuPencyFacter($usege_type)
         * 
         * =============== Request ================================
         * $road_width_in_sft       -> road width in sft
         * $area_in_dml             -> Vacand Land Area of Plot (in Decimal)
         * $ulb_type_id             -> (1 ->  Municipal Corporations, 2-> Nagar Parishad, 3->Nagar Panchayat)
         * $usege_type              ->  TENANTED Or SELF OCCUPIED
         * 
         * ==============Response ================================
         * $Tax (Yearly TAX Of Vacand Land )
         */
        $rate = $this->RentalRate($road_width_in_sft, "2016-04-01", $ulb_type_id);
        $Tax = $this->DecimalToSqtMeter($area_in_dml) * $rate * $this->OccuPencyFacter($usege_type);
        return ['TotalTax' => $Tax];
    }
    public function vacantRulSet2(float $road_width_in_sft, float $area_in_dml, int $ulb_type_id, string $usege_type): array
    {
        /**
         * Description  -> This Rule Is Applicable For Vacant Land 
         * Validity     -> 2022-04-01 To Till Now (2022-2023 to Till Now)
         * =============== Formula==================================
         * ------------------------------------------------------
         * | Tax = Area(sqmt) X Rental Rate X Occupancy Facter  |
         * ------------------------------------------------------
         * Area             = DecimalToSqtMeter($area_in_dml)
         * Rental Rate      = RentalRate($road_width_in_sft,"2022-04-01",$ulb_type_id)
         * Occupancy Facter = OccuPencyFacter($usege_type)
         * 
         * =============== Request ================================
         * $road_width_in_sft       -> road width in sft
         * $area_in_dml             -> Vacand Land Area of Plot (in Decimal)
         * $ulb_type_id             -> (1 ->  Municipal Corporations, 2-> Nagar Parishad, 3->Nagar Panchayat)
         * $usege_type              ->  TENANTED Or SELF OCCUPIED
         * 
         * ==============Response ================================
         * $Tax (Yearly TAX Of Vacand Land ) 
         */
        $rate = $this->RentalRate($road_width_in_sft, "2022-04-01", $ulb_type_id);
        $Tax = $this->DecimalToSqtMeter($area_in_dml) * $rate * $this->OccuPencyFacter($usege_type);
        return ['TotalTax' => $Tax];
    }
<<<<<<< HEAD

    public function buildingRulSet1(int $ulb_id, float $buildupAreaSqft, int $usegeTypeID, int $zoneID, int $constructionTypeID, int $PropertyTypeID, bool $Residential100, $buildupDate): array
    {
=======
    
    public function buildingRulSet1(int $ulb_id,float $buildupAreaSqft, int $usegeTypeID, int $zoneID, int $constructionTypeID,int $PropertyTypeID, bool $Residential100, $buildupDate ):array 
    {   
>>>>>>> master
        /**
         * Description  -> This Rule Is Applicable For Building (Individual Floar)
         * Validity     -> Befor 2017-03-31 To Till Now (Befor 2016-2017) 
         * =============== Formula==================================
         * ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
         * |  ARV = (Buildup Area (in Sq. Ft) X Rental Value) - Tax Pecentage | Case 1.) Usege Type Is Residential Then 30%                                                                                                        |
         * |                                                                  | Case 2.) Usage Type Is Commercial Then  15%                                                                                                        |
         * |                                                                  | Case 3.) If Property Type Is Indipendent And 100% Residential And Building Older Than 25years From 1967-1968 (<'1942-04-01')  Then +10%            |
         * |                                                                                                                                                                                                                       |
         * | HoldingTax        = ARV X 12.5%                                                                                                                                                                                       |
         * | LatrinTax         = ARV X 7.5%                                                                                                                                                                                        |
         * | WaterTax          = ARV X 7.5%                                                                                                                                                                                        |
         * | HealthTax         = ARV X 6.25%                                                                                                                                                                                       |
         * | EducationTax      = ARV X 5.0%                                                                                                                                                                                        |
         * |                                                                                                                                                                                                                       |
         * | YearTax = HoldingTax + LatrinTax + WaterTax + HealthTax + EducationTax                                                                                                                                                |
         * ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
         * ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ 
         * =======================Reference Variable====================
         * Buildup Area         = $buildupAreaSqft
         * Rental Value         = RentalValue($ulb_id,$usegeTypeID,$zoneID,$constructionTypeID)
         * Tax Pecentage        = $pesentage
         * ARV                  = $arv
         * HoldingTax           = $holding_tax
         * LatrinTax            = $latine_tax
         * WaterTax             = $water_tax
         * HealthTax            = $health_tax
         * EducationTax         = $education_tax
         * YearTax              = $tax
         * 
         * ========================Request=============================
         * $ulb_id               -> ulb_ward_masters->id (Table)
         * $buildupAreaSqft      -> Built Up Area (in Sq. Ft)
         * $usegeTypeID          -> prop_param_usage_types ->id   |Case 1.) If usage_type = RESIDENTIAL  Then 1;
         *                                                        |Case 2.) If usage_type != RESIDENTIAL Then 2;
         * $zoneID               -> | 1-> Zone1
         *                          | 2-> Zone2
         * $constructionTypeID   -> prop_param_occupancy_types->id 
         * $PropertyTypeID       -> prop_param_property_types->id 
         * $Residential100       -> |true or false (hole building on the dasis of that date)
         * $buildupDate          -> construction date Of floar
         * 
         * ======================= Response ==========================
         * Array = ["ARV"           => $arv,
                    "TotalTax"      => $tax,
                    "buildupAreaSqft" =>$buildupAreaSqft,
                    "usegeTypeID"       => $usegeTypeID,
                    "zoneID"            =>$zoneID,                
                    "PropertyTypeID"    => $PropertyTypeID,
                    "Residential100"   =>$Residential100,
                    "buildupDate"     =>$buildupDate,
                    "constructionTypeID" =>$constructionTypeID,
                    "pesentage"     =>$pesentage,
                    "HoldingTax"    => $holding_tax,
                    "LatineTax"     => $latine_tax,
                    "WaterTax"      => $water_tax,
                    "HealthTax"     => $health_tax,
                    "EducationTax"  => $education_tax
            ]
         */
        $rentalVal = $this->RentalValue($ulb_id, $usegeTypeID, $zoneID, $constructionTypeID);
        $arv = $buildupAreaSqft * $rentalVal;
        $pesentage = 0;
        $tax = 0;
<<<<<<< HEAD

        if ($Residential100 && $PropertyTypeID == 2 && $buildupDate < '1942-04-01')
            $pesentage += 10;
        if ($usegeTypeID == 1)
            $pesentage += 30;
        elseif ($usegeTypeID == 2)
            $pesentage += 15;
=======
        if($Residential100 && $PropertyTypeID==2 && $buildupDate<'1942-04-01')
            $pesentage +=10;
        if($usegeTypeID==1)
            $pesentage +=30;
        elseif($usegeTypeID==2)
            $pesentage +=15; 
>>>>>>> master

        $arv = $arv - ($arv * $pesentage) / 100;

        $holding_tax = ($arv * 12.5) / 100;
        $latine_tax = ($arv * 7.5) / 100;
        $water_tax = ($arv * 7.5) / 100;
        $health_tax = ($arv * 6.25) / 100;
        $education_tax = ($arv * 5.0) / 100;
        $tax = ($holding_tax + $latine_tax + $water_tax + $health_tax + $education_tax);
        return [
            "ARV"           => $arv,
            "TotalTax"      => $tax,
            "buildupAreaSqft" => $buildupAreaSqft,
            "usegeTypeID"       => $usegeTypeID,
            "zoneID"            => $zoneID,
            "PropertyTypeID"    => $PropertyTypeID,
            "Residential100"   => $Residential100,
            "buildupDate"     => $buildupDate,
            "constructionTypeID" => $constructionTypeID,
            "pesentage"     => $pesentage,
            "HoldingTax"    => $holding_tax,
            "LatineTax"     => $latine_tax,
            "WaterTax"      => $water_tax,
            "HealthTax"     => $health_tax,
            "EducationTax"  => $education_tax
        ];
    }
    public function buildingRulSet2(int $ulb_id, float $buildupAreaSqft, int $usegeTypeID, int $OccuTypeID, float $road_width_in_sft, int $constructionTypeID, $buildupDate)
    {
        /**
         * Description -> This Rule Is Applicable For Building (Individual Floar)
         * Validity     -> 2016-04-01 To 2022-03-31  (2016-2017 to 2021-2022) 
         * =============== Formula==================================
         * -------------------------------------------------------------------------------------------------------------
         * ARV  = CARPET AREA X USAGE FACTOR X OCCUPANCY FACTOR X RENTAL RATE                                           |
         *                                                                                                              |
         * CARPET AREA          = BuildupArea X  Percentage   |Case 1.)Residential 70%                                  |
         *                                                    |Case 2.)Commercial  80%                                  |
         *                                                                                                              |
         * USAGE FACTOR         = UsageFacter($usegeTypeID,'2016-04-01')                                                |
         *                                                                                                              |
         * OCCUPANCY FACTOR     = OccuPencyFacter($OccuType)                                                            |
         *                                                                                                              |
         * RENTAL RATE          = BuildingRentalValue($ulb_id,$GetRodeTypeID,$constructionTypeID,'2016-04-01')          |
         *                                                                                                              |
         * YearTax = ARV x 2%                                                                                           |
         * -------------------------------------------------------------------------------------------------------------
         * =======================Reference Variable====================
         * BuildupArea          = $buildupAreaSqft
         * USAGE FACTOR         = $UsageFacter    = UsageFacter($usegeTypeID,'2016-04-01') | $usegeTypeID = From Parameter
         * OCCUPANCY FACTOR     = $OccuPencyFacter= OccuPencyFacter($OccuType)     |   $OccuType = Config::get("PropertyConstaint.OCCUPANCY-TYPE.$OccuTypeID")
         * RENTAL RATE          = $RentalValue    = BuildingRentalValue($ulb_id,$GetRodeTypeID,$constructionTypeID,'2016-04-01') | $ulb_id             = From Parameter
         *                                                                                                                          | $GetRodeTypeID      = getRodeType($road_width_in_sft,'2016-04-01',1)[0]->id | $road_width_in_sft = From Parameter
         *                                                                                                                          | $constructionTypeID = From Parameter      
         * CARPET AREA          = $CarpetArea
         * ARV                  = $ARV
         * YearTax              = $tax
         * 
         * ========================Request=============================
         * $ulb_id               -> ulb_ward_masters->id (Table)
         * $buildupAreaSqft      -> Built Up Area (in Sq. Ft)
         * $usegeTypeID          -> prop_param_usage_types ->id   |Case 1.) If usage_type = RESIDENTIAL  Then 1;
         *                                                        |Case 2.) If usage_type != RESIDENTIAL Then 2;
         * 
         * $OccuTypeID           -> prop_param_occupancy_types->id   |Case 1.) TENANTED  Then 1;
         *                                                           |Case 2.) SELF OCCUPIED Then 2;
         * 
         * $road_width_in_sft    -> Road Width in sqf
         * $constructionTypeID   -> prop_param_occupancy_types->id 
         * $buildupDate          -> construction date Of floar
         * ======================= Response ===========================
         * ARRAY = ["ARV"                =>$ARV,
                "TotalTax"          => $tax,
                "buildupAreaSqft"   =>$buildupAreaSqft,
                "CarpetPresent"     =>$CarpetPresent,
                "CarpetArea"        =>$CarpetArea,
                "usegeTypeID"       => $usegeTypeID,
                "UsageFacter"       =>$UsageFacter,                
                "OccuType"          => $OccuType,
                "OccuPencyFacter"   =>$OccuPencyFacter,
                "GetRodeTypeID"     =>$GetRodeTypeID,
                "constructionTypeID" =>$constructionTypeID,
                "RentalValue"       => $RentalValue,
            ]
         */
        $OccuType = Config::get("PropertyConstaint.OCCUPANCY-TYPE.$OccuTypeID");
        $OccuPencyFacter = $this->OccuPencyFacter($OccuType);
        $UsageFacter = $this->UsageFacter($usegeTypeID, '2016-04-01');
        $GetRodeTypeID = $this->getRodeType($road_width_in_sft, '2016-04-01', 1)[0]->id ?? 0;
        $RentalValue = $this->BuildingRentalValue($ulb_id, $GetRodeTypeID, $constructionTypeID, '2016-04-01');

        $CarpetPresent = 80;
        if ($usegeTypeID == 1)
            $CarpetPresent = 70;

        $CarpetArea = $buildupAreaSqft * $CarpetPresent / 100;
        $ARV = $CarpetArea * $UsageFacter * $OccuPencyFacter * $RentalValue;
        $tax = $ARV * 2 / 100;
        $data = [
            "ARV"                => $ARV,
            "TotalTax"          => $tax,
            "buildupAreaSqft"   => $buildupAreaSqft,
            "CarpetPresent"     => $CarpetPresent,
            "CarpetArea"        => $CarpetArea,
            "usegeTypeID"       => $usegeTypeID,
            "UsageFacter"       => $UsageFacter,
            "OccuType"          => $OccuType,
            "OccuPencyFacter"   => $OccuPencyFacter,
            "GetRodeTypeID"     => $GetRodeTypeID,
            "constructionTypeID" => $constructionTypeID,
            "RentalValue"       => $RentalValue,
        ];
        return  $data;
    }

    public function buildingRulSet3(int $ulb_id, float $buildupAreaSqft, int $usegeTypeID, int $OccuTypeID, float $road_width_in_sft, int $constructionTypeID, bool $Residential100, int $PropertyTypeID, string $ward_no, $buildupDate): array
    {
        /**
         * Description -> This Rule Is Applicable For Building (Individual Floar)
         * Validity     -> 2022-04-01 To Till Now  (2022-2023 to Till Now) 
         * =============== Formula==================================
         * 
         * ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
         *                                                                                                                                                                                  |
         * TAX = CIRCLE RATE X BUILDUP AREA X OCCUPANCY FACTOR  X TAX PERCENTAGE X CALCULATION FACTOR  X MATRIX FACTOR RATE (only in case of 100% residential property)                     |
         *                                                                                                                                                                                  |
         * CIRCLE RATE      = CircleRate($ulb_id,$ward_no,$columnName)                                                                                                                                                              |
         *                                                                                                                                                                                  |
         * BUILDUP AREA     = $buildupAreaSqft                                                                                                                                              |
         *                                                                                                                                                                                  |
         * OCCUPANCY FACTOR = OccuPencyFacter($OccuType)                                                                                                                                    |
         *                                                                                                                                                                                  |
         * TAX PERCENTAGE   = $TaxPresent   | Case 1.) Residential 0.0.75%                                                                                                                  |
         *                                  | Case 2.) Commercial  AND BUILDUP AREA < 25000 Then 0.15% Else 0.20%                                                                           |
         *                                                                                                                                                                                  |
         * CALCULATION FACTOR= UsageFacter($usegeTypeID,'2022-04-01')                                                                                                                       |
         *                                                                                                                                                                                  |
         * MATRIX FACTOR RATE=  Config::get("PropertyConstaint.MATRIX-FACTOR.$GetRodeTypeID.$constructionTypeID") (only in case of 100% residential property)                               |
         * ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
         * 
         * =======================Reference Variable====================
         * BUILDUP AREA         = $buildupAreaSqft
         * CALCULATION FACTOR   = $UsageFacter    = UsageFacter($usegeTypeID,'2016-04-01') | $usegeTypeID = From Parameter
         * OCCUPANCY FACTOR     = $OccuPencyFacter= OccuPencyFacter($OccuType)     |   $OccuType = Config::get("PropertyConstaint.OCCUPANCY-TYPE.$OccuTypeID")     
         * TAX PERCENTAGE       = $TaxPresent
         * MATRIX FACTOR RATE   = $MatrixFactor(only in case of 100% residential property) = Config::get("PropertyConstaint.MATRIX-FACTOR.$GetRodeTypeID.$constructionTypeID")         
         * YearTax              = $Tax
         * 
         * ========================Request=============================
         * $ulb_id               -> ulb_ward_masters->id (Table)
         * $buildupAreaSqft      -> Built Up Area (in Sq. Ft)
         * $usegeTypeID          -> prop_param_usage_types ->id   |Case 1.) If usage_type = RESIDENTIAL  Then 1;
         *                                                        |Case 2.) If usage_type != RESIDENTIAL Then 2;
         * 
         * $OccuTypeID           -> prop_param_occupancy_types->id   |Case 1.) TENANTED  Then 1;
         *                                                           |Case 2.) SELF OCCUPIED Then 2;
         * 
         * $road_width_in_sft    -> Road Width in sqf
         * $constructionTypeID   -> prop_param_occupancy_types->id 
         * $Residential100       -> |true or false (hole building on the dasis of that date)
         * $newWardId            -> ulb_ward_masters->id (Table)
         * $buildupDate          -> construction date Of floar 
         * 
         * ======================= Response ===========================
         * ARRAY = ["TotalTax"           =>$Tax,
                "buildupAreaSqft"   =>$buildupAreaSqft,
                "CircalRate"        =>$CircalRate,
                "TaxPresent"        =>$TaxPresent,
                "usegeTypeID"       => $usegeTypeID,
                "CalculationFactor" =>$CalculationFactor,                
                "OccuType"          => $OccuType,
                "OccuPencyFacter"   =>$OccuPencyFacter,
                "GetRodeTypeID"     =>$GetRodeTypeID,
                "constructionTypeID" =>$constructionTypeID,
                "MatrixFactor"      => $MatrixFactor
            ]
         */
        $OccuType = Config::get("PropertyConstaint.OCCUPANCY-TYPE.$OccuTypeID");
        $OccuPencyFacter = $this->OccuPencyFacter($OccuType);
        $CalculationFactor = $this->UsageFacter($usegeTypeID, '2022-04-01');
        $GetRodeTypeID = $this->getRodeType($road_width_in_sft, '2016-04-01', 1)[0]->id ?? 0;

        $resCom = $usegeTypeID == 1 ? 1 : 2;
        $use = Config::get("PropertyConstaint.CIRCALE-RATE-USAGE.$resCom");
        $road_id = $this->getRodeType($road_width_in_sft, '2022-04-01', 1)[0]->id;
        $road = Config::get("PropertyConstaint.CIRCALE-RATE-ROAD.$road_id");
        $prop_id = $PropertyTypeID == 3 ? 0 : $constructionTypeID;
        $prop = Config::get("PropertyConstaint.CIRCALE-RATE-PROP.$prop_id");
        $columnName = $use . $prop . $road;
        $CircalRate = $this->CircleRate($ulb_id, $ward_no, $columnName);

        $MatrixFactor = Config::get("PropertyConstaint.MATRIX-FACTOR.$GetRodeTypeID.$constructionTypeID") ?? 1.00;

        $TaxPresent = 0.20;
        if ($buildupAreaSqft < 25000)
            $TaxPresent = 0.15;
        elseif ($usegeTypeID == 1)
            $TaxPresent = 0.075;

        $Tax = ($CircalRate * $buildupAreaSqft * $OccuPencyFacter * $TaxPresent * $CalculationFactor) / 100;
        if ($Residential100) {
            $Tax *= $MatrixFactor;
        }

        $data = [
            "TotalTax" => $Tax,
            "buildupAreaSqft" => $buildupAreaSqft,
            "CircalRate" => $CircalRate,
            "TaxPresent" => $TaxPresent,
            "usegeTypeID" => $usegeTypeID,
            "CalculationFactor" => $CalculationFactor,
            "OccuType" => $OccuType,
            "OccuPencyFacter" => $OccuPencyFacter,
            "GetRodeTypeID" => $GetRodeTypeID,
            "constructionTypeID" => $constructionTypeID,
            "MatrixFactor" => $MatrixFactor
        ];
        return  $data;
    }

    #================================ End RuleSet ===================================================

    #================================ Tax Calulation ================================================
    // public function fromRuleEmplimenteddate(): String 
    // {
    //     /* ------------------------------------------------------------
    //         * Calculation
    //         * ------------------------------------------------------------
    //         * subtract 12 year from current date
    //     */
    //     return Carbon::now()->subYear(12)->format("Y-m-d");
    // } 
    public function BuildingTax($request): array
    {
        $this->TotalTax = [];
        $M_property = [];
        $L_PropertyTypeId       = $request['propertyType'];
        $L_road_width           = $request['roadType'];
        $L_ward_no              = $request['ward_no'];
        $L_zoneId               = $request['zone'] ?? 1;
        $L_ulb_id               = $request['ulb_id'];
        $L_ploat_area           = $request['areaOfPlot'];
        $L_ulb_type_id          = $request['ulb_type_id'] ?? 1;
        if ($request['isMobileTower']) {
            $this->FYearQuater = [];
            $L_towerArea               = $request['towerArea'];
            $L_towerInstallationDate   = $request['towerInstallationDate'] ?? '2016-04-01';

            $M_property['mobileTower']['isMobileTower'] = $request['isMobileTower'];
            $M_property['mobileTower']['towerArea'] = $request['towerArea'];
            $M_property['mobileTower']['towerInstallationDate'] = $request['towerInstallationDate'];

            if ($L_towerInstallationDate <= '2016-04-01') {
                $L_towerInstallationDate = '2016-04-01';
            }
            $L_usageTypeId          = 45;
            $L_constructionTypeId   = 1;
            $L_occuType             = 1;
            $L_MobileRuleSet = $this->getFYearQutery($L_towerInstallationDate, '', 1);
            $M_property['mobileTower']['RuleSet'] = $L_MobileRuleSet;

<<<<<<< HEAD
            foreach ($L_MobileRuleSet as $key => $ruls) {
                $this->Tax = [];
                foreach ($ruls as $rul) {
                    switch ($rul['rule_set']) {
=======
            foreach($L_MobileRuleSet as $key=>$ruls)
            {   
                $this->Tax=[];                
                foreach($ruls as $rul)
                {                     
                    switch($rul['rule_set'])
                    {
>>>>>>> master
                        case "buildingRulSet1": //buildingRulSet1(int $ulb_id,float $buildupAreaSqft, int $usegeTypeID, int $zoneID, int $constructionTypeID,int $PropertyTypeID, bool $Residential100, $buildupDate ):array
                            if (isset($this->Tax[$key])) {
                                $yearly = $this->Tax[$key];
                            } else
                                $yearly = $this->buildingRulSet1($L_ulb_id, $L_towerArea, $L_usageTypeId, $L_zoneId, $L_constructionTypeId, $L_PropertyTypeId, false, $request['towerInstallationDate']);
                            $quaterly = $yearly['TotalTax'] / 4;
                            $this->Tax[$key] = $yearly;
                            $this->Tax[$key][$rul['qtr']] = $quaterly;
                            $this->Tax[$key]['due_date'] = $rul['due_date'];
                            break;
                        case "buildingRulSet2": //buildingRulSet2(int $ulb_id,float $buildupAreaSqft, int $usegeTypeID,int $OccuTypeID, float $road_width_in_sft,int $constructionTypeID, $buildupDate )
<<<<<<< HEAD
                            if (isset($this->Tax[$key]))                                                 // $L_zoneId,
                            {
                                $yearly = $this->Tax[$key];
                            } else
                                $yearly = $this->buildingRulSet2($L_ulb_id, $L_towerArea, $L_usageTypeId, $L_occuType, $L_road_width, $L_constructionTypeId, $request['towerInstallationDate']);
                            $quaterly = $yearly['TotalTax'] / 4;
                            $this->Tax[$key] = $yearly;
                            $this->Tax[$key][$rul['qtr']] = $quaterly;
                            $this->Tax[$key]['due_date'] = $rul['due_date'];
                            break;
                        case "buildingRulSet3": //buildingRulSet3(int $ulb_id,float $buildupAreaSqft, int $usegeTypeID,int $OccuTypeID, float $road_width_in_sft,int $constructionTypeID, bool $Residential100 ,int $PropertyTypeID,string $ward_no,$buildupDate ):array
                            if (isset($this->Tax[$key])) {
                                $yearly = $this->Tax[$key];
                            } else
                                $yearly = $this->buildingRulSet3($L_ulb_id, $L_towerArea, $L_usageTypeId, $L_occuType, $L_road_width, $L_constructionTypeId, false, $L_PropertyTypeId, $L_ward_no, $request['towerInstallationDate']);
                            $quaterly = $yearly['TotalTax'] / 4;
                            $this->Tax[$key] = $yearly;
                            $this->Tax[$key][$rul['qtr']] = $quaterly;
                            $this->Tax[$key]['due_date'] = $rul['due_date'];
                            break;
                        default:
                            "Undefined RuleSet";
                    }
                }
                $M_property['mobileTower']['Tax'][$key] = $this->Tax[$key];
            }
        }

        if ($request['isHoardingBoard']) {
            $this->FYearQuater = [];
=======
                                                if(isset($this->Tax[$key]))                                                 // $L_zoneId,
                                                {
                                                    $yearly = $this->Tax[$key];                                                                                                        
                                                }
                                                else
                                                    $yearly = $this->buildingRulSet2($L_ulb_id, $L_towerArea, $L_usageTypeId,$L_occuType,$L_road_width, $L_constructionTypeId,$request['towerInstallationDate']);
                                                $quaterly = $yearly['TotalTax']/4;
                                                $this->Tax[$key] = $yearly ; 
                                                $this->Tax[$key][$rul['qtr']] = $quaterly ;
                                                $this->Tax[$key]['due_date'] = $rul['due_date'];
                                                break;
                        case "buildingRulSet3"://buildingRulSet3(int $ulb_id,float $buildupAreaSqft, int $usegeTypeID,int $OccuTypeID, float $road_width_in_sft,int $constructionTypeID, bool $Residential100 ,int $PropertyTypeID,string $ward_no,$buildupDate ):array
                                                if(isset($this->Tax[$key]))
                                                {
                                                    $yearly = $this->Tax[$key];                                                                                                        
                                                }
                                                else
                                                    $yearly = $this->buildingRulSet3($L_ulb_id, $L_towerArea, $L_usageTypeId,$L_occuType,$L_road_width, $L_constructionTypeId, false,$L_PropertyTypeId,$L_ward_no,$request['towerInstallationDate']);
                                                $quaterly = $yearly['TotalTax']/4;
                                                $this->Tax[$key] = $yearly ; 
                                                $this->Tax[$key][$rul['qtr']] = $quaterly ;
                                                $this->Tax[$key]['due_date'] = $rul['due_date'];
                                                break;
                        default:  "Undefined RuleSet";

                    }                    
                }                
                $M_property['mobileTower']['Tax'][$key] = $this->Tax[$key];
            }
            
        }        
        if($request['isHoardingBoard'])
        {
            $this->FYearQuater =[];
>>>>>>> master
            $L_isHoardingBoard          = $request['isHoardingBoard'];
            $L_hoardingArea             = $request['hoardingArea'];
            $L_hoardingInstallationDate = $request['hoardingInstallationDate'] ?? '2016-04-01';

            $M_property['hoardingBoard']['isHoardingBoard'] = $request['isHoardingBoard'];
            $M_property['hoardingBoard']['hoardingArea'] = $request['hoardingArea'];
            $M_property['hoardingBoard']['hoardingInstallationDate'] = $request['hoardingInstallationDate'];

            if ($L_hoardingInstallationDate <= '2016-04-01') {
                $L_hoardingInstallationDate = '2016-04-01';
            }
            $L_usageTypeId          = 45;
            $L_constructionTypeId   = 1;
            $L_occuType             = 1;

            $L_HoardingBoardRuleSet = $this->getFYearQutery($L_hoardingInstallationDate, '', 1);

            $M_property['hoardingBoard']['RuleSet'] = $L_HoardingBoardRuleSet;

            foreach ($L_HoardingBoardRuleSet as $key => $ruls) {
                $this->Tax = [];
                foreach ($ruls as $rul) {
                    switch ($rul['rule_set']) {
                        case "buildingRulSet1": //buildingRulSet1(int $ulb_id,float $buildupAreaSqft, int $usegeTypeID, int $zoneID, int $constructionTypeID,int $PropertyTypeID, bool $Residential100, $buildupDate ):array
                            if (isset($this->Tax[$key])) {
                                $yearly = $this->Tax[$key];
                            } else
                                $yearly = $this->buildingRulSet1($L_ulb_id, $L_hoardingArea, $L_usageTypeId, $L_zoneId, $L_constructionTypeId, $L_PropertyTypeId, false, $request['towerInstallationDate']);
                            $quaterly = $yearly['TotalTax'] / 4;
                            $this->Tax[$key] = $yearly;
                            $this->Tax[$key][$rul['qtr']] = $quaterly;
                            $this->Tax[$key]['due_date'] = $rul['due_date'];
                            break;
                        case "buildingRulSet2": //buildingRulSet2(int $ulb_id,float $buildupAreaSqft, int $usegeTypeID,int $OccuTypeID, float $road_width_in_sft,int $constructionTypeID, $buildupDate )
                            if (isset($this->Tax[$key]))                                                 // $L_zoneId,
                            {
                                $yearly = $this->Tax[$key];
                            } else
                                $yearly = $this->buildingRulSet2($L_ulb_id, $L_hoardingArea, $L_usageTypeId, $L_occuType, $L_road_width, $L_constructionTypeId, $request['towerInstallationDate']);
                            $quaterly = $yearly['TotalTax'] / 4;
                            $this->Tax[$key] = $yearly;
                            $this->Tax[$key][$rul['qtr']] = $quaterly;
                            $this->Tax[$key]['due_date'] = $rul['due_date'];
                            break;
                        case "buildingRulSet3": //buildingRulSet3(int $ulb_id,float $buildupAreaSqft, int $usegeTypeID,int $OccuTypeID, float $road_width_in_sft,int $constructionTypeID, bool $Residential100 ,int $PropertyTypeID,string $ward_no,$buildupDate ):array
                            if (isset($this->Tax[$key])) {
                                $yearly = $this->Tax[$key];
                            } else
                                $yearly = $this->buildingRulSet3($L_ulb_id, $L_hoardingArea, $L_usageTypeId, $L_occuType, $L_road_width, $L_constructionTypeId, false, $L_PropertyTypeId, $L_ward_no, $request['towerInstallationDate']);
                            $quaterly = $yearly['TotalTax'] / 4;
                            $this->Tax[$key] = $yearly;
                            $this->Tax[$key][$rul['qtr']] = $quaterly;
                            $this->Tax[$key]['due_date'] = $rul['due_date'];
                            break;
                        default:
                            "Undefined RuleSet";
                    }
                }
                $M_property['hoardingBoard']['Tax'][$key] = $this->Tax[$key];
            }
        }

        if ($request['isPetrolPump']) {
            $this->FYearQuater = [];
            $L_isPetrolPump          = $request['isPetrolPump'];
            $L_undergroundArea         = $request['undergroundArea'];
            $L_petrolPumpCompletionDate = $request['petrolPumpCompletionDate'] ?? '20216-04-01';

            $M_property['petrolPump']['isPetrolPump'] = $request['isPetrolPump'];
            $M_property['petrolPump']['undergroundArea'] = $request['undergroundArea'];
            $M_property['petrolPump']['petrolPumpCompletionDate'] = $request['petrolPumpCompletionDate'];

            if ($L_petrolPumpCompletionDate <= '2016-04-01') {
                $L_petrolPumpCompletionDate = '2016-04-01';
            }
            $L_usageTypeId          = 45;
            $L_constructionTypeId   = 1;
            $L_occuType             = 1;
            $L_PetrolPumpRuleSet = $this->getFYearQutery($L_petrolPumpCompletionDate, '', 1);
            $M_property['petrolPump']['RuleSet'] = $L_PetrolPumpRuleSet;

            foreach ($L_PetrolPumpRuleSet as $key => $ruls) {
                $this->Tax = [];
                foreach ($ruls as $rul) {
                    switch ($rul['rule_set']) {
                        case "buildingRulSet1": //buildingRulSet1(int $ulb_id,float $buildupAreaSqft, int $usegeTypeID, int $zoneID, int $constructionTypeID,int $PropertyTypeID, bool $Residential100, $buildupDate ):array
                            if (isset($this->Tax[$key])) {
                                $yearly = $this->Tax[$key];
                            } else
                                $yearly = $this->buildingRulSet1($L_ulb_id, $L_undergroundArea, $L_usageTypeId, $L_zoneId, $L_constructionTypeId, $L_PropertyTypeId, false, $request['towerInstallationDate']);
                            $quaterly = $yearly['TotalTax'] / 4;
                            $this->Tax[$key] = $yearly;
                            $this->Tax[$key][$rul['qtr']] = $quaterly;
                            $this->Tax[$key]['due_date'] = $rul['due_date'];
                            break;
                        case "buildingRulSet2": //buildingRulSet2(int $ulb_id,float $buildupAreaSqft, int $usegeTypeID,int $OccuTypeID, float $road_width_in_sft,int $constructionTypeID, $buildupDate )
                            if (isset($this->Tax[$key]))                                                 // $L_zoneId,
                            {
                                $yearly = $this->Tax[$key];
                            } else
                                $yearly = $this->buildingRulSet2($L_ulb_id, $L_undergroundArea, $L_usageTypeId, $L_occuType, $L_road_width, $L_constructionTypeId, $request['towerInstallationDate']);
                            $quaterly = $yearly['TotalTax'] / 4;
                            $this->Tax[$key] = $yearly;
                            $this->Tax[$key][$rul['qtr']] = $quaterly;
                            $this->Tax[$key]['due_date'] = $rul['due_date'];
                            break;
                        case "buildingRulSet3": //buildingRulSet3(int $ulb_id,float $buildupAreaSqft, int $usegeTypeID,int $OccuTypeID, float $road_width_in_sft,int $constructionTypeID, bool $Residential100 ,int $PropertyTypeID,string $ward_no,$buildupDate ):array
                            if (isset($this->Tax[$key])) {
                                $yearly = $this->Tax[$key];
                            } else
                                $yearly = $this->buildingRulSet3($L_ulb_id, $L_undergroundArea, $L_usageTypeId, $L_occuType, $L_road_width, $L_constructionTypeId, false, $L_PropertyTypeId, $L_ward_no, $request['towerInstallationDate']);
                            $quaterly = $yearly['TotalTax'] / 4;
                            $this->Tax[$key] = $yearly;
                            $this->Tax[$key][$rul['qtr']] = $quaterly;
                            $this->Tax[$key]['due_date'] = $rul['due_date'];
                            break;
                        default:
                            "Undefined RuleSet";
                    }
                }
                $M_property['petrolPump']['Tax'][$key] = $this->Tax[$key];
            }
        }

        

<<<<<<< HEAD
        if ($L_PropertyTypeId != 4) {
            $this->FYearQuater = [];
            $L_residential100 = array_filter($request['floor'], function ($val) {
                return $val['useType'] != 1;
            });
            $L_residential100 = !empty($L_residential100) ? false : true;
            foreach ($request['floor'] as $keys => $floor) {
                $this->Tax = [];
                $reqFromDate = $floor["dateFrom"];
                $reqUptoDate = $floor["dateUpto"];
                $ruleSets = $this->getFYearQutery($reqFromDate, $reqUptoDate);
                $floor['RuleSet'] = $ruleSets;
                foreach ($ruleSets as $key => $val) {
                    foreach ($val as $rul) {
=======
        if($L_PropertyTypeId!=4)
        {            
            $this->FYearQuater =[];
            $L_residential100 = array_filter($request['floor'],function($val){
                return $val['useType']!=1;
            });
            $L_residential100 = !empty($L_residential100)?false:true;             
            foreach($request['floor'] as $keys=>$floor)
            {
                $this->Tax=[];
                $reqFromDate = $floor["dateFrom"];
                $reqUptoDate = $floor["dateUpto"];
                $fromRuleEmplimenteddate = fromRuleEmplimenteddate();
                if ($fromRuleEmplimenteddate > $reqFromDate) 
                {
                    $reqFromDate = $fromRuleEmplimenteddate;
                }
                $ruleSets = $this->getFYearQutery($reqFromDate,$reqUptoDate);            
                $floor['RuleSet'] = $ruleSets; 
                foreach($ruleSets as $key=>$val)
                { 
                    foreach($val as $rul)
                    {                    
>>>>>>> master
                        $L_usageTypeId          = $floor['useType'];
                        $L_buildupArea          = $floor['buildupArea'];
                        $L_constructionTypeId   = $floor['occupancyType'];
                        $L_buildutDate          = $floor['dateFrom'];
                        $L_occuType             = $floor['occupancyType'];
                        switch ($rul['rule_set']) {
                            case "buildingRulSet1": //buildingRulSet1(int $ulb_id,float $buildupAreaSqft, int $usegeTypeID, int $zoneID, int $constructionTypeID,int $PropertyTypeID, bool $Residential100, $buildupDate ):array
                                if (isset($this->Tax[$key])) {
                                    $yearly = $this->Tax[$key];
                                } else
                                    $yearly = $this->buildingRulSet1($L_ulb_id, $L_buildupArea, $L_usageTypeId, $L_zoneId, $L_constructionTypeId, $L_PropertyTypeId, $L_residential100, $L_buildutDate);
                                $quaterly = $yearly['TotalTax'] / 4;
                                $this->Tax[$key] = $yearly;
                                $this->Tax[$key][$rul['qtr']] = $quaterly;
                                $this->Tax[$key]['due_date'] = $rul['due_date'];
                                break;
                            case "buildingRulSet2": //buildingRulSet2(int $ulb_id,float $buildupAreaSqft, int $usegeTypeID,int $OccuTypeID, float $road_width_in_sft,int $constructionTypeID, $buildupDate )
                                if (isset($this->Tax[$key]))                                                 // $L_zoneId,
                                {
                                    $yearly = $this->Tax[$key];
                                } else
                                    $yearly = $this->buildingRulSet2($L_ulb_id, $L_buildupArea, $L_usageTypeId, $L_occuType, $L_road_width, $L_constructionTypeId, $L_buildutDate);
                                $quaterly = $yearly['TotalTax'] / 4;
                                $this->Tax[$key] = $yearly;
                                $this->Tax[$key][$rul['qtr']] = $quaterly;
                                $this->Tax[$key]['due_date'] = $rul['due_date'];
                                break;
                            case "buildingRulSet3": //buildingRulSet3(int $ulb_id,float $buildupAreaSqft, int $usegeTypeID,int $OccuTypeID, float $road_width_in_sft,int $constructionTypeID, bool $Residential100 ,int $PropertyTypeID,string $ward_no,$buildupDate ):array
                                if (isset($this->Tax[$key])) {
                                    $yearly = $this->Tax[$key];
                                } else
                                    $yearly = $this->buildingRulSet3($L_ulb_id, $L_buildupArea, $L_usageTypeId, $L_occuType, $L_road_width, $L_constructionTypeId, $L_residential100, $L_PropertyTypeId, $L_ward_no, $L_buildutDate);
                                $quaterly = $yearly['TotalTax'] / 4;
                                $this->Tax[$key] = $yearly;
                                $this->Tax[$key][$rul['qtr']] = $quaterly;
                                $this->Tax[$key]['due_date'] = $rul['due_date'];
                                break;
                            default:
                                "Undefined RuleSet";
                        }
                    }
                }
                $floor['Tax'] = $this->Tax;
                $M_property['floorsDtl'][$keys] = $floor;
<<<<<<< HEAD
            }
        } elseif ($L_PropertyTypeId == 4) {
            $this->FYearQuater = [];
=======
    
            }  
        }   
        elseif($L_PropertyTypeId==4) 
        {   
            $this->FYearQuater =[];
>>>>>>> master
            $L_landOccupationDate = $request['landOccupationDate'];
            if ($L_landOccupationDate <= '2016-04-01') {
                $L_landOccupationDate = '2016-04-01';
            }
            $L_vacandLandRuleSet = $this->getFYearQutery($L_landOccupationDate, '', $L_PropertyTypeId);
            // dd($L_vacandLandRuleSet);
            $M_property['vacandLand']['RuleSet'] = $L_vacandLandRuleSet;
            $OccuType = Config::get("PropertyConstaint.OCCUPANCY-TYPE.2");
<<<<<<< HEAD
            foreach ($L_vacandLandRuleSet as $key => $ruls) {
                $this->Tax = [];
                foreach ($ruls as $rul) {
                    switch ($rul['rule_set']) {
=======
            foreach($L_vacandLandRuleSet as $key=>$ruls)
            {   
                $this->Tax=[];
                foreach($ruls as $rul)
                {                    
                    switch($rul['rule_set'])
                    {
                        
>>>>>>> master
                        case "vacantRulSet1": //vacantRulSet1(float $road_width_in_sft,float $area_in_dml, int $ulb_type_id, string $usege_type):float
                            if (isset($this->Tax[$key])) {
                                $yearly = $this->Tax[$key];
                            } else
                                $yearly = $this->vacantRulSet1($L_road_width, $L_ploat_area, $L_ulb_type_id, $OccuType);
                            $quaterly = $yearly['TotalTax'] / 4;
                            $this->Tax[$key] = $yearly;
                            $this->Tax[$key][$rul['qtr']] = $quaterly;
                            $this->Tax[$key]['due_date'] = $rul['due_date'];
                            break;
                        case "vacantRulSet2": //vacantRulSet2(float $road_width_in_sft,float $area_in_dml,int $ulb_type_id, string $usege_type):float
                            if (isset($this->Tax[$key]))                                                 // $L_zoneId,
                            {
                                $yearly = $this->Tax[$key];
                            } else
                                $yearly = $this->vacantRulSet2($L_road_width, $L_ploat_area, $L_ulb_type_id, $OccuType);
                            $quaterly = $yearly['TotalTax'] / 4;
                            $this->Tax[$key] = $yearly;
                            $this->Tax[$key][$rul['qtr']] = $quaterly;
                            $this->Tax[$key]['due_date'] = $rul['due_date'];
                            break;
                        default:
                            "Undefined RuleSet";
                    }
                }
                $M_property['vacandLand']['Tax'][$key] = $this->Tax[$key];
            }
        }
        $this->TotalTax = $M_property;
        return  $M_property;
    }

<<<<<<< HEAD
    public function getFYearQutery($fromdate, $uptodate = null, $PropertyTypeID = 1)
    {
        if (!$uptodate)
=======
    public function getFYearQutery($fromdate,$uptodate=null,$PropertyTypeID=1)
    {  
               
        if(!$uptodate)
>>>>>>> master
            $uptodate = Carbon::now()->format('Y-m-d');

        $carbonDate = Carbon::createFromFormat("Y-m-d", $fromdate);
        $MM = (int) $carbonDate->format("m");
        $YYYY = (int) $carbonDate->format("Y");
        $carbonUpto =  Carbon::createFromFormat("Y-m-d", $uptodate);
        // print_var($fromdate ."  " . $uptodate."    " .$PropertyTypeID);
        if ($carbonDate->format("Y-m") < $carbonUpto->format("Y-m")) {
            $m = 4;
            if ($MM % 4 != 0) {
                $m = 4 - $MM;
            }
            $L_fromdate = $carbonDate->addMonth($m)->format('Y-m-d');
            $fquater = getQtr($fromdate);
            $FYear = getFYear($fromdate);
            $this->FYearQuater[$FYear][] = $this->getRulsets($fromdate, $PropertyTypeID)[0];
            if ($fquater == 3 && getFYear($fromdate) != getFYear($uptodate)) {
                $dd = (int) $carbonDate->format("d");
<<<<<<< HEAD
                $fromdate = Carbon::createFromFormat("Y-m-d", $L_fromdate)->subDay($dd)->format('Y-m-d');

                $this->FYearQuater[$FYear][] = $this->getRulsets($fromdate, $PropertyTypeID)[0];
=======
                $fromdate = Carbon::createFromFormat("Y-m-d",$L_fromdate)->subDay($dd)->format('Y-m-d');                
                $this->FYearQuater[$FYear][]= $this->getRulsets($fromdate,$PropertyTypeID)[0];
>>>>>>> master
            }
            return ($this->getFYearQutery($L_fromdate, $uptodate, $PropertyTypeID));
        }
        if ($fromdate >= $uptodate) {
            $FYear = getFYear($uptodate);
            $fromdate = Carbon::createFromFormat("Y-m-d", $uptodate)->format('Y-m-d');
            $rul = $this->getRulsets($uptodate, $PropertyTypeID)[0];
            if ($this->FYearQuater[$FYear][sizeof($this->FYearQuater[$FYear]) - 1] != $rul)
                $this->FYearQuater[$FYear][] = $rul;
        }

        return $this->FYearQuater;
    }

    public function getRulsets($dateFrom, $PropertyTypeID = 1)
    {
        $fromRuleEmplimenteddate = fromRuleEmplimenteddate();
        $reqFromDate = $dateFrom;
        $ruleSets = [];
        // dd($reqFromDate);
        if ($fromRuleEmplimenteddate > $reqFromDate) {
            $reqFromDate = $fromRuleEmplimenteddate;
        }

        // is implimented rule set 1 (before 2016-2017), (2016-2017 TO 2021-2022), (2021-2022 TO TILL NOW)
        if ("2016-04-01" > $reqFromDate && $PropertyTypeID == 1) {
            $ruleSets[] = [
                "rule_set" => "buildingRulSet1",
                "qtr" => getQtr($reqFromDate),
                "due_date" => getQuaterDueDate($reqFromDate)
            ];
            return $ruleSets;
        }
        // is implimented rule set 2 (2016-2017 TO 2021-2022), (2021-2022 TO TILL NOW)
        elseif ("2022-04-01" > $reqFromDate && $PropertyTypeID == 1) {
            $ruleSets[] = [
                "rule_set" => "buildingRulSet2",
                "qtr" => getQtr($reqFromDate),
                "due_date" => getQuaterDueDate($reqFromDate)
            ];
            return $ruleSets;
        }
        // is implimented rule set 3 (2021-2022 TO TILL NOW)
        elseif ("2022-04-01" <= $reqFromDate && $PropertyTypeID == 1) {
            $ruleSets[] = [
                "rule_set" => "buildingRulSet3",
                "qtr" => getQtr($reqFromDate),
                "due_date" => getQuaterDueDate($reqFromDate)
            ];
            return $ruleSets;
        }
        // is implimented rule set 2 (2016-2017 TO 2021-2022), (2021-2022 TO TILL NOW)
        elseif ("2022-04-01" > $reqFromDate && $PropertyTypeID == 4) {
            $ruleSets[] = [
                "rule_set" => "vacantRulSet1",
                "qtr" => getQtr($reqFromDate),
                "due_date" => getQuaterDueDate($reqFromDate)
            ];
            return $ruleSets;
        }
        // is implimented rule set 3 (2021-2022 TO TILL NOW)
        elseif ("2022-04-01" <= $reqFromDate && $PropertyTypeID == 4) {
            $ruleSets[] = [
                "rule_set" => "vacantRulSet2",
                "qtr" => getQtr($reqFromDate),
                "due_date" => getQuaterDueDate($reqFromDate)
            ];
            return $ruleSets;
        }
    }


    #================================ End Tax Calculation============================================





}
