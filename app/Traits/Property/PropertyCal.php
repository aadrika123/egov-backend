<?php

namespace App\Traits\Property;

use App\Models\PropParamUsageTypeMultFactor;
use App\Models\PropParamVacantRentalRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Traits\Auth;
use Exception;
use Illuminate\Support\Facades\Config;
use phpDocumentor\Reflection\PseudoTypes\True_;

trait PropertyCal
{
    use Auth;   //Trate use 

    /**
     * created by sandeep bara 
     * date 01-09-2022
     * private folder not editable befor edit Please conforme Not Effecte Other Method 
    */

    public function getAllVacantLandRentalRate():array // stdcl object array
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
        $redis = Redis::connection();//Redis::del("AllVacantLandRentalRate");
        $rentalVal = json_decode(Redis::get("AllVacantLandRentalRate"))??null;
        if(!$rentalVal)
        {
            $rentalVal = DB::select("select prop_road_type_masters.id,road_type,range_from_sqft,range_upto_sqft,prop_param_road_types.effective_date,
                                        rate,ulb_type_id
                                    from prop_param_vacant_rental_rates
                                    join prop_param_road_types on prop_param_vacant_rental_rates.prop_road_typ_id=prop_param_road_types.prop_road_typ_id
                                        and prop_param_vacant_rental_rates.effective_date = prop_param_road_types.effective_date
                                    join prop_road_type_masters on prop_param_road_types.prop_road_typ_id = prop_road_type_masters.id");
            
            $this->AllVacantLandRentalRateSet($redis,$rentalVal);
        }
        return  $rentalVal;
    }

    public function getRodeType(float $with_in_arear_sft,  $effective_date, int $ulb_type_id) : array //stdcl object array
    {
        /**
         * Description -> This function Do Filteration Using Retiving Stdclass Objecte Array
         * ================ Super Function ===================
         * 1. getAllVacantLandRentalRate()
         * 
         * ================ Dependent Function ===============
         * 1. getRentalRate()
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
        try{
            $vacantLandRentalRate = $this->getAllVacantLandRentalRate();
            $with_in_arear_sft = floatRound($with_in_arear_sft,2);
            $roadType = array_filter($vacantLandRentalRate,function($val)use( $with_in_arear_sft,$effective_date,$ulb_type_id){            
                if($val->ulb_type_id ==$ulb_type_id && $val->effective_date ==$effective_date && $val->range_from_sqft<=$with_in_arear_sft && ($val->range_upto_sqft?$val->range_upto_sqft>=$with_in_arear_sft:true)){ 
                             
                    return true;
                }
            }); 
            if(!$roadType)
            {
                throw new Exception("Road Type Not Found");
            }
            return array_values($roadType);             
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
            die;
        }
    }
    public function getRentalRate(float $road_width_in_sft, $effective_date, int $ulb_type_id):float
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
        try{
            $road_type = $this->getRodeType($road_width_in_sft, $effective_date, $ulb_type_id);
            if(!$road_type)  
            {
                throw new Exception("Road Type Rental Rate Not Found");
            }      
            return $road_type[0]->rate??0.0;
        }
        catch(Exception $e)
        {
            echo $e->getMessage(); 
            die;
        }
    }
    public function getAllOccuPencyFacter(string $usege_type=null):array //stdcls object array
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
         * 1. getOccuPencyFacter()
         * 
         * ================= Response ============================
         * Stdcl Object Array 
         * 
         * ================= Variable ============================
         * $redis               -> Redis Object
         * $OccuPencyFacter     -> Stdcl Object (Store And Return This Variable)
         * 
        */
        try{
            $redis = Redis::connection();
            $OccuPencyFacter = json_decode(Redis::get('OccuPencyFacter'))??null;
            if(!$OccuPencyFacter)
            {
                $OccuPencyFacter = DB::select("select * from prop_occupency_facters where status =1 ");
                $this->OccuPencyFacterSet($redis,$OccuPencyFacter);
    
            }
            if($usege_type)
                $OccuPencyFacter = array_filter($OccuPencyFacter,function($val)use($usege_type){
                    return $val->occupancy_name==$usege_type?true:false;
                });
            if(!$OccuPencyFacter)
            {
                throw new Exception("Occupency Facters Not Found");
            }
            return  array_values($OccuPencyFacter);
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
            die;
        }
    }

    public function getOccuPencyFacter(string $usege_type):float
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
        return  $OccuPencyFacter = $this->getAllOccuPencyFacter($usege_type)[0]->mult_factor??0;
    }

    #===================== Core Function =================================
    public function aqrtMeterToFeet(float $num) : float
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
    public function aqrtFeetToMeter(float $num) : float
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
        return ($num * 40.46485) ;
    }

    # =================== End Core Function ==============================
    
    public function getAllRentalValue(int $ulb_id):array // array of stdcl
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
         * 1. getRentalValue()
         * 
         * =====================Function Usage ======================
         * AllRentalValueSet($redis,$ulb_id,$AllRentalValue)  // Trate/Auth.php
        */
        $redis = Redis::connection();
        $AllRentalValue = json_decode(Redis::get("AllRentalValue:$ulb_id"))??null;
        if(!$AllRentalValue)
        {
            $AllRentalValue=DB::select("select * from prop_param_rental_values where status=1");
            $this->AllRentalValueSet($redis,$ulb_id,$AllRentalValue);
        }
        return $AllRentalValue;

    }

    public function getRentalValue(int $ulb_id,int $usege_type_id, int $zone_id, int $construction_type_id):float
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
        try{
            $AllRentalValue = $this->getAllRentalValue($ulb_id);
            $RentalValue = array_filter($AllRentalValue,function($val) use($usege_type_id,$zone_id,$construction_type_id){
                if($val->usage_types_id == $usege_type_id && $val->zone_id == $zone_id && $val->construction_types_id == $construction_type_id)
                    return true;
            });
            if(!$RentalValue)
            {
                throw new Exception("ARV Rental Rate Not Found");
            }
            return array_values($RentalValue)[0]->rate??0.0;

        }
        catch(Exception $e)
        {
            echo $e->getMessage();
            die;
        }
    }

    public function getAllBuildingUsageFacter(string $UsageType =null):array //stdcl object
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
        $AllBuildingUsageFacter = json_decode(Redis::get("AllBuildingUsageFacter"))??null;
        if(!$AllBuildingUsageFacter)
        { 
            $AllBuildingUsageFacter = DB::select("SELECT prop_param_usage_types.id,usage_type,mult_factor,effective_date 
                                                  FROM prop_param_usage_type_mult_factors 
                                                  JOIN prop_param_usage_types ON prop_param_usage_types.id = prop_param_usage_type_mult_factors.prop_param_usage_types_id
                                                        AND prop_param_usage_types.status =1
                                                  where prop_param_usage_type_mult_factors.status =1 
                                                  ORDER BY prop_param_usage_type_mult_factors.effective_date ");
            $this->AllBuildingUsageFacterSet($redis,$AllBuildingUsageFacter);
        }
        if($UsageType)
            $AllBuildingUsageFacter = array_filter($AllBuildingUsageFacter,function($val)use($UsageType){
            return $val->usage_type==$UsageType?true:false;
        });
        return  array_values($AllBuildingUsageFacter);
    }

    public function UsageFacter(int $UsageTypeID, $effectiveDate) :float
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
        try{
            $AllBuildingUsageFacter = $this->getAllBuildingUsageFacter();
            $AllBuildingUsageFacter = array_filter($AllBuildingUsageFacter,function($val)use($UsageTypeID,$effectiveDate){
                return $val->id==$UsageTypeID && $val->effective_date==$effectiveDate?true:false;
            });
            $AllBuildingUsageFacter = array_values($AllBuildingUsageFacter);
            if(!$AllBuildingUsageFacter)
            {
                throw New Exception("Usage Facter Not Found");
            }
            return $AllBuildingUsageFacter[0]->mult_factor??0.0;
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
            die;
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
         * getBuildingRentalValue()
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
        $AllBuildingRentalValue = json_decode(Redis::get("AllBuildingRentalValue:$ulb_id"))??null;        
        if(!$AllBuildingRentalValue)
        { 
            $AllBuildingRentalValue = DB::select("SELECT prop_road_typ_id,construction_types_id,
                                                    ulb_id,x, rate,prop_param_building_rental_consts.effective_date 
                                                  FROM prop_param_building_rental_rates 
                                                  JOIN prop_param_building_rental_consts 
                                                        ON prop_param_building_rental_consts.effective_date = prop_param_building_rental_rates.effective_date                                                        
                                                  where prop_param_building_rental_rates.status = 1 
                                                        AND  prop_param_building_rental_consts.status =1 
                                                        AND prop_param_building_rental_consts.ulb_id = $ulb_id
                                                  ORDER BY prop_param_building_rental_consts.effective_date ");
            $this->AllBuildingRentalValueSet($redis,$ulb_id,$AllBuildingRentalValue);
        }
        return  array_values($AllBuildingRentalValue);
    }

    public function getBuildingRentalValue(int $ulb_id,int $RoadTypeId, int $constructionTypeID,$effectiveDate) :float
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
        try{
            $AllBuildingUsageFacter = $this->getAllBuildingRentalValue($ulb_id);
           
            $AllBuildingUsageFacter = array_filter($AllBuildingUsageFacter,function($val)use($RoadTypeId,$constructionTypeID,$effectiveDate){
                return ($val->prop_road_typ_id==$RoadTypeId && $val->construction_types_id==$constructionTypeID && $val->effective_date==$effectiveDate )?true:false;
            });
            $AllBuildingUsageFacter = array_values($AllBuildingUsageFacter); 
            if(!$AllBuildingUsageFacter)
            {
                throw new Exception("Building Rental Value Not Found");
            }
            return $AllBuildingUsageFacter[0]->rate*$AllBuildingUsageFacter[0]->x??0.0; 
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
            die;
        }
        
    }

    # ===================RuleSet Start ===================================
    /**
     * Tax = area(sqmt) x rental_rate x occupancy_factor;
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
    public function vacantRulSet1(float $road_width_in_sft,float $area_in_dml, int $ulb_type_id, string $usege_type):float
    {
        /**
         * Description  -> This Rule Is Applicable For Vacant Land 
         * Validity     -> 2016-04-01 to 2022-03-31 (2016-2017 to 2021-2022)
         * =============== Formula==================================
         * ------------------------------------------------------
         * | Tax = Area(sqmt) X Rental Rate X Occupancy Facter  |
         * ------------------------------------------------------
         * Area             = DecimalToSqtMeter($area_in_dml)
         * Rental Rate      = getRentalRate($road_width_in_sft,"2016-04-01",$ulb_type_id)
         * Occupancy Facter = getOccuPencyFacter($usege_type)
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
        $rate = $this->getRentalRate($road_width_in_sft,"2016-04-01",$ulb_type_id);
        return $Tax = $this->DecimalToSqtMeter($area_in_dml) * $rate * $this->getOccuPencyFacter($usege_type);
    }
    public function vacantRulSet2(float $road_width_in_sft,float $area_in_dml,int $ulb_type_id, string $usege_type):float
    {
        /**
         * Description  -> This Rule Is Applicable For Vacant Land 
         * Validity     -> 2022-04-01 To Till Now (2022-2023 to Till Now)
         * =============== Formula==================================
         * ------------------------------------------------------
         * | Tax = Area(sqmt) X Rental Rate X Occupancy Facter  |
         * ------------------------------------------------------
         * Area             = DecimalToSqtMeter($area_in_dml)
         * Rental Rate      = getRentalRate($road_width_in_sft,"2022-04-01",$ulb_type_id)
         * Occupancy Facter = getOccuPencyFacter($usege_type)
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
        $rate = $this->getRentalRate($road_width_in_sft,"2022-04-01",$ulb_type_id);
        return $Tax = $this->DecimalToSqtMeter($area_in_dml) * $rate * $this->getOccuPencyFacter($usege_type);
    }
    
    public function buildingRulSet1(int $ulb_id,float $buildupAreaSqft, int $usegeTypeID, int $zoneID, int $constructionTypeID,int $PropertyTypeID, bool $Residential100, $buildupDate ):array 
    {
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
         * Rental Value         = getRentalValue($ulb_id,$usegeTypeID,$zoneID,$constructionTypeID)
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
        */
        $rentalVal = $this->getRentalValue($ulb_id,$usegeTypeID,$zoneID,$constructionTypeID);
        $arv = $buildupAreaSqft * $rentalVal;
        $pesentage = 0;
        $tax = 0;

        if($Residential100 && $PropertyTypeID==2 && $buildupDate<'1942-04-01')
            $pesentage +=10;
        if($usegeTypeID==1)
            $pesentage +=30;
        elseif($usegeTypeID==2)
            $pesentage +=15; 

        $arv = $arv - ($arv*$pesentage)/100;

        $holding_tax = ($arv*12.5)/100;
        $latine_tax = ($arv*7.5)/100;
        $water_tax = ($arv*7.5)/100;
        $health_tax = ($arv*6.25)/100;
        $education_tax = ($arv*5.0)/100;
        $tax = ($holding_tax + $latine_tax + $water_tax + $health_tax + $education_tax );
        return [
            "ARV"           => $arv,
            "TotalTax"      => $tax,
            "pesentage"     =>$pesentage,
            "HoldingTax"    => $holding_tax,
            "LatineTax"     => $latine_tax,
            "WaterTax"      => $water_tax,
            "HealthTax"     => $health_tax,
            "EducationTax"  => $education_tax
        ];
    }
    public function buildingRulSet2(int $ulb_id,float $buildupAreaSqft, int $usegeTypeID,int $OccuTypeID, float $road_width_in_sft,int $constructionTypeID, $buildupDate )
    { 
        $OccuType = Config::get("PropertyConstaint.OCCUPANCY-TYPE.$OccuTypeID");
        $OccuPencyFacter = $this->getOccuPencyFacter($OccuType);
        $UsageFacter = $this->UsageFacter($usegeTypeID,'2016-04-01');
        $GetRodeTypeID = $this->getRodeType($road_width_in_sft,'2016-04-01',1)[0]->id??0;
        $RentalValue = $this->getBuildingRentalValue($ulb_id,$GetRodeTypeID,$constructionTypeID,'2016-04-01');
        
        $CarpetPresent = 80;
        if($usegeTypeID==1)
            $CarpetPresent = 70;

        $CarpetArea = $buildupAreaSqft * $CarpetPresent/100;
        $ARV = $CarpetArea * $UsageFacter * $OccuPencyFacter * $RentalValue;

        $data=["ARV" =>$ARV,
                "buildupAreaSqft"=>$buildupAreaSqft,
                "CarpetPresent" =>$CarpetPresent,
                "CarpetArea" =>$CarpetArea,
                "usegeTypeID" => $usegeTypeID,
                "UsageFacter" =>$UsageFacter,                
                "OccuType" => $OccuType,
                "OccuPencyFacter" =>$OccuPencyFacter,
                "GetRodeTypeID" =>$GetRodeTypeID,
                "constructionTypeID" =>$constructionTypeID,
                "RentalValue" => $RentalValue,
        ];
        return  $data;
    }

    public function buildingRulSet3(int $ulb_id,float $buildupAreaSqft, int $usegeTypeID,int $OccuTypeID, float $road_width_in_sft,int $constructionTypeID, bool $Residential100 ,$buildupDate )
    {
        /**
         *  CIRCLE RATE 
         *  X BUILDUP AREA 
         *  X OCCUPANCY FACTOR 
         *  X TAX PERCENTAGE 
         *  X CALCULATION FACTOR 
         *  X MATRIX FACTOR RATE (only in case of 100% residential property)
         * ----------------------------------------
         * = PROPERTY TAX 
         * 
         *  */ 
        $OccuType = Config::get("PropertyConstaint.OCCUPANCY-TYPE.$OccuTypeID");
        $OccuPencyFacter = $this->getOccuPencyFacter($OccuType);        
        $CalculationFactor = $this->UsageFacter($usegeTypeID,'2022-04-01');        
        $GetRodeTypeID = $this->getRodeType($road_width_in_sft,'2016-04-01',1)[0]->id??0;
        $CircalRate = 3366;
        $MatrixFactor = Config::get("PropertyConstaint.MATRIX-FACTOR.$GetRodeTypeID.$constructionTypeID")??1.00;
        
        $TaxPresent = 0.20;
        if($buildupAreaSqft>=25000)
            $TaxPresent = 0.15;
        elseif($usegeTypeID==1)
            $TaxPresent = 0.75;

        $Tax = ($CircalRate * $buildupAreaSqft * $OccuPencyFacter * $TaxPresent * $CalculationFactor)/100;
        if($Residential100)
        {
            $Tax *=$MatrixFactor; 
        }

        $data = ["Tax" =>$Tax,
                "buildupAreaSqft"=>$buildupAreaSqft,
                "CircalRate" =>$CircalRate,
                "TaxPresent" =>$TaxPresent,
                "usegeTypeID" => $usegeTypeID,
                "CalculationFactor" =>$CalculationFactor,                
                "OccuType" => $OccuType,
                "OccuPencyFacter" =>$OccuPencyFacter,
                "GetRodeTypeID" =>$GetRodeTypeID,
                "constructionTypeID" =>$constructionTypeID,
                "MatrixFactor" => $MatrixFactor
        ];
        return  $data;
    }

    #================================ End RuleSet ===================================================
    
}