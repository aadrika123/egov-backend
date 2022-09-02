<?php

namespace App\Traits\Property;

use App\Models\PropParamVacantRentalRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Traits\Auth;
use phpDocumentor\Reflection\PseudoTypes\True_;

trait PropertyCal
{
    use Auth;

    public function getAllVacantLandRentalRate():array
    {
        $redis = Redis::connection();//Redis::del("AllVacantLandRentalRate");
        $rentalVal = json_decode(Redis::get("AllVacantLandRentalRate"))??null;
        if(!$rentalVal)
        {
            // $rentalVal = PropParamVacantRentalRate::select(
            //                                                 "prop_road_type_masters.id",
            //                                                 "road_type",
            //                                                 "prop_param_road_types.range_from_sqft",
            //                                                 "prop_param_road_types.range_upto_sqft",
            //                                                 "prop_param_road_types.effective_date",
            //                                                 "rate",
            //                                                 "ulb_type_id"
            //                                                 )
            //             ->join("prop_param_road_types",function($join){
            //                 $join->on( "prop_param_vacant_rental_rates.prop_road_typ_id","=","prop_param_road_types.prop_road_typ_id")
            //                 ->where("prop_param_vacant_rental_rates.effective_date", "=","prop_param_road_types.effective_date");
            //             })
            //             ->join("prop_road_type_masters",function($join){
            //                 $join->on( "prop_param_road_types.prop_road_typ_id","=","prop_road_type_masters.id");
            //             })
            //             ->where('prop_road_type_masters.status',1)
            //             ->where('prop_param_road_types.status',1)
            //             ->where("prop_param_vacant_rental_rates.status",1)
            //             ->get();
            // $rentalVal = adjToArray($rentalVal);
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

    public function getRodeType(float $with_in_arear_sft,  $effective_date,$ulb_type_id) : array
    {
        $vacantLandRentalRate = $this->getAllVacantLandRentalRate();
        $roadType = array_filter($vacantLandRentalRate,function($val)use( $with_in_arear_sft,$effective_date,$ulb_type_id){            
            if($val->ulb_type_id ==$ulb_type_id && $val->effective_date >=$effective_date && $val->range_from_sqft>=$with_in_arear_sft && $val->range_upto_sqft<=$with_in_arear_sft)
                return true;
        }); 
        return array_values($roadType);    
    }
    public function getRentalRate(float $road_width_in_sft, $effective_date,$ulb_type_id):float
    {
        $road_type = $this->getRodeType($road_width_in_sft, $effective_date,$ulb_type_id);        
        return $road_type[0]->rate??0.00;
    }
    public function getAllOccuPencyFacter($usege_type=null):array //stdcls object
    {
        $redis = Redis::connection();
        $OccuPencyFacter = json_decode(Redis::get('OccuPencyFacter'))??null;
        if(!$OccuPencyFacter)
        {
            $OccuPencyFacter = DB::select("select * from prop_occupency_facters where status =1 ");

        }
        if($usege_type)
            $OccuPencyFacter = array_filter($OccuPencyFacter,function($val)use($usege_type){
                return $val->occupancy_name==$usege_type?true:false;
            });
        //dd($OccuPencyFacter);
        return  array_values($OccuPencyFacter);
    }
    public function getOccuPencyFacter($usege_type):float
    {
        return  $OccuPencyFacter = $this->getAllOccuPencyFacter($usege_type)[0]->mult_factor??0;
    }
    /**
     * Tax = area(sqmt) x rental_rate x occupancy_factor;
     * ==============OCCUPANCY FACTER ==================
     * SELF     -> 1
     * TENATED  -> 1.5 
     * =============RENTAL RATE ========================
     * 
     */
    public function vacantRulSet1(float $road_width_in_sft,float $area_in_dml,$ulb_type_id,$usege_type):float
    {
        $rate = $this->getRentalRate($road_width_in_sft,"2016-04-01",$ulb_type_id);
        return $Tax = $this->DecimalToSqtMeter($area_in_dml) * $rate * $this->getOccuPencyFacter($usege_type);
    }
    public function vacantRulSet2(float $road_width_in_sft,float $area_in_dml,$ulb_type_id,$usege_type):float
    {
        $rate = $this->getRentalRate($road_width_in_sft,"2022-04-01",$ulb_type_id);
        return $Tax = $this->DecimalToSqtMeter($area_in_dml) * $rate * $this->getOccuPencyFacter($usege_type);
    }

    public function aqrtMeterToFeet(float $num) : float
    {
        return $num * 10.76391042; 
    }
    public function aqrtFeetToMeter(float $num) : float
    {
        return $num / 10.76391042; 
    }
    public function DecimalToSqtMeter(float $num)
    {
        return ($num * 40.46485) ;
    }

    public function getAllRentalValue():array // array of stdcl
    {
        $redis = Redis::connection();
        $AllRentalValue = json_decode(Redis::get('AllRentalValue'))??null;
        if(!$AllRentalValue)
        {
            $AllRentalValue=DB::select("select * from prop_param_rental_values where status=1");
            $this->AllRentalValueSet($redis,$AllRentalValue);
        }
        return $AllRentalValue;

    }
    public function getRentalValue(int $usege_type_id, int $zone_id, int $construction_type_id):float
    {
        $AllRentalValue = $this->getAllRentalValue();
        $RentalValue = array_filter($AllRentalValue,function($val) use($usege_type_id,$zone_id,$construction_type_id){
            if($val->usage_types_id == $usege_type_id && $val->zone_id == $zone_id && $val->construction_types_id == $construction_type_id)
                return true;
        });
        return array_values($RentalValue)[0]->rate??0.00;
    }
    /**
     * $arv = $bulibupArea * $rentalVal;
     */
    
    public function buildinRulSet1(float $bulibupAreaSqft, int $usegeTypeID, int $zoneID, int $constructionTypeID, $buildupDate ):array 
    {
        $rentalVal = $this->getRentalValue($usegeTypeID,$zoneID,$constructionTypeID);
        $arv = $bulibupAreaSqft * $rentalVal;
        $pesentage = 0;
        $tax = 0;

        if($buildupDate<'1942-04-01')
            $pesentage +=10;
        if($usegeTypeID==1)
            $pesentage +=30;
        elseif($usegeTypeID==2)
            $pesentage +=15; 

        $arv = $arv - ($arv*$pesentage)/100;

        $holding_tax = ($arv*7.5)/100;
        $latine_tax = ($arv*7.5)/100;
        $water_tax = ($arv*6.25)/100;
        $health_tax = ($arv*12.5)/100;
        $education_tax = ($arv*5.0)/100;
        $tax = ($holding_tax + $latine_tax + $water_tax + $health_tax + $education_tax );
        return [
            "ARV"           => $arv,
            "TotalTax"      => $tax,
            "HoldingTax"    => $holding_tax,
            "LatineTax"     => $latine_tax,
            "WaterTax"      => $water_tax,
            "HealthTax"     => $health_tax,
            "EducationTax"  => $education_tax
        ];
    }
}