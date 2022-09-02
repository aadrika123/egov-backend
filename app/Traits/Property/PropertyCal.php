<?php

namespace App\Traits\Property;

use App\Models\PropParamVacantRentalRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

trait PropertyCal
{
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
    public function getOccuPencyFacter($usege_type=null):object
    {
        $redis = Redis::connection();
        $OccuPencyFacter = json_decode(Redis::get('OccuPencyFacter'))??null;
        if(!$OccuPencyFacter)
        {
            $OccuPencyFacter = DB::select("select * from prop_occupency_facters where status =1 ");

        }
        if($usege_type)
        $OccuPencyFacter = $OccuPencyFacter->find($usege_type);
        return  $OccuPencyFacter;
    }
    /**
     * Tax = area(sqmt) x rental_rate x occupancy_factor;
     * ==============OCCUPANCY FACTER ==================
     * SELF     -> 1
     * TENATED  -> 1.5 
     * =============RENTAL RATE ========================
     * 
     */
    public function vacantRulSet1(float $road_width_in_sft,$ulb_type_id):float
    {
        $rate = $this->getRentalRate($road_width_in_sft,"2016-04-01",$ulb_type_id);
        return $Tax = $this->aqrtFeetToMeter($road_width_in_sft) * $rate * $this->getOccuPencyFacter();
    }

    public function aqrtMeterToFeet(float $num) : float
    {
        return $num * 10.76391042; 
    }
    public function aqrtFeetToMeter(float $num) : float
    {
        return $num / 10.76391042; 
    }
}