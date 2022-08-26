<?php

namespace App\Repository\Property;

use App\Models\PropPropertie;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EloquentProperty implements PropertyRepository
{
    /**
     * | Created On-26-08-2022 
     * | Created By-Sandeep Bara
     * ------------------------------------------------------------------------------------------
     * | Property Module all operations 
    */
    public function getPropIdByWardNoHodingNo(array $input)
    {
        try {
            
            $data = PropPropertie::select("prop_properties.id",
                                            "prop_properties.new_holding_no",
                                            "prop_properties.prop_address",
                                            "prop_properties.prop_type_mstr_id",
                                            "owner_name",
                                            "guardian_name",
                                            "mobile_no",                                            
                                            )
                                    ->join('ulb_ward_masters', function($join){
                                        $join->on("ulb_ward_masters.id","=","prop_properties.ward_mstr_id");
                                    })
                                    ->leftJoin(
                                        DB::raw("(SELECT prop_owners.property_id,
                                                        string_agg(prop_owners.owner_name,', ') as owner_name,
                                                        string_agg(prop_owners.guardian_name,', ') as guardian_name,
                                                        string_agg(prop_owners.mobile_no::text,', ') as mobile_no
                                                FROM prop_owners 
                                                WHERE prop_owners.status = 1
                                                GROUP BY prop_owners.property_id
                                                )owner_details
                                                    "),
                                        function($join){
                                            $join->on("owner_details.property_id","=","prop_properties.id")
                                            ;
                                        }
                                    ) 
                                    ->where("prop_properties.ward_mstr_id",$input['ward_mstr_id'])
                                    ->where(function($where)use($input){
                                        $where->orwhere('prop_properties.holding_no', 'ILIKE', '%'.$input['holding_no'].'%')
                                        ->orwhere('prop_properties.new_holding_no', 'ILIKE', '%'.$input['holding_no'].'%');
                                    })
                                    ->get();
            return $data;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    public function getPropertyById($id)
    {
        if(!is_numeric($id))
        {
            $id = Crypt::decryptString($id);
        }
        $data = PropPropertie::select("*")
                        ->where('id',$id)
                        ->first();
        return $data;
    }
}