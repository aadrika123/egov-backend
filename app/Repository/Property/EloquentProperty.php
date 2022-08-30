<?php

namespace App\Repository\Property;

use App\Models\PropFloorDetail;
use App\Models\PropOwner;
use App\Models\PropPropertie;
use App\Models\TransferModeMaster;
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
        try{
            if(!is_numeric($id))
            {
                $id = Crypt::decryptString($id);
            }
            $data = PropPropertie::select("*")
                            ->where('id',$id)
                            ->first();
            return $data;
        }
        catch(Exception $e){
            echo $e->getMessage();
        }
        
    }
    public function getOwnerDtlByPropId($prop_id)
    {
        try{
            if(!is_numeric($prop_id))
            {
                $prop_id = Crypt::decryptString($prop_id);
            }
            $data = PropOwner::select("*")
                            ->where('status',1)
                            ->where('property_id',$prop_id)
                            ->get();
            return $data;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function getFloorDtlByPropId($prop_id)
    {
        try{
            if(!is_numeric($prop_id))
            {
                $prop_id = Crypt::decryptString($prop_id);
            }
            $data = PropFloorDetail::select("*")
                            ->where('status',1)
                            ->where('property_id',$prop_id)
                            ->get();
            return $data;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function getAllTransferMode()
    {
        try{
            $data = TransferModeMaster::select("id","transfer_mode")
                                ->where("status",1)
                                ->get();
            return $data;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
    public function transFerSafToProperty($saf_id,$current_user)
    {
        if(!is_numeric($saf_id))
        {
            $saf_id = Crypt::decrypt($saf_id);
        }
        $sql = " insert into safs(
            has_previous_holding_no ,previous_holding_id ,previous_ward_mstr_id, transfer_mode_mstr_id ,saf_no ,holding_no ,
            ward_mstr_id , ownership_type_mstr_id, prop_type_mstr_id ,appartment_name ,flat_registry_date ,zone_mstr_id,
            no_electric_connection ,elect_consumer_no ,elect_acc_no ,elect_bind_book_no, elect_cons_category ,
            building_plan_approval_no , building_plan_approval_date ,water_conn_no , water_conn_date,khata_no,plot_no ,
            village_mauja_name ,road_type_mstr_id ,area_of_plot ,prop_address,prop_city , prop_dist ,prop_pin_code ,
            is_corr_add_differ , corr_address ,corr_city ,corr_dist , corr_pin_code , is_mobile_tower,tower_area ,
            tower_installation_date ,is_hoarding_board ,hoarding_area , hoarding_installation_date , is_petrol_pump ,under_ground_area ,
            petrol_pump_completion_date,is_water_harvesting,land_occupation_date ,payment_status, doc_verify_status ,
            doc_verify_date ,doc_verify_emp_details_id,doc_verify_cancel_remarks , field_verify_status , field_verify_date ,
            field_verify_emp_details_id,emp_details_id ,status , apply_date , saf_pending_status,assessment_type , doc_upload_status ,
            saf_distributed_dtl_id, prop_dtl_id, prop_state,corr_state ,holding_type, ip_address , property_assessment_id ,
            new_ward_mstr_id, percentage_of_property_transfer ,apartment_details_id , ".'"current_user"'." ,initiator_id , finisher_id ,
            workflow_id , ulb_id,is_escalate , citizen_id ,escalate_by,deleted_at ,created_at ,updated_at 
        )
        
        select has_previous_holding_no ,previous_holding_id ,previous_ward_mstr_id, transfer_mode_mstr_id ,saf_no ,holding_no ,
            ward_mstr_id , ownership_type_mstr_id, prop_type_mstr_id ,appartment_name ,flat_registry_date ,zone_mstr_id,
            no_electric_connection ,elect_consumer_no ,elect_acc_no ,elect_bind_book_no, elect_cons_category ,
            building_plan_approval_no , building_plan_approval_date ,water_conn_no , water_conn_date,khata_no,plot_no ,
            village_mauja_name ,road_type_mstr_id ,area_of_plot ,prop_address,prop_city , prop_dist ,prop_pin_code ,
            is_corr_add_differ , corr_address ,corr_city ,corr_dist , corr_pin_code , is_mobile_tower,tower_area ,
            tower_installation_date ,is_hoarding_board ,hoarding_area , hoarding_installation_date , is_petrol_pump ,under_ground_area ,
            petrol_pump_completion_date,is_water_harvesting,land_occupation_date ,payment_status, doc_verify_status ,
            doc_verify_date ,doc_verify_emp_details_id,doc_verify_cancel_remarks , field_verify_status , field_verify_date ,
            field_verify_emp_details_id,emp_details_id ,status , apply_date , saf_pending_status,assessment_type , doc_upload_status ,
            saf_distributed_dtl_id, prop_dtl_id, prop_state,corr_state ,holding_type, ip_address , property_assessment_id ,
            new_ward_mstr_id, percentage_of_property_transfer ,apartment_details_id , $current_user ,initiator_id , finisher_id ,
            workflow_id , ulb_id,is_escalate , citizen_id ,escalate_by,deleted_at ,created_at ,updated_at 
        from active_saf_details 
        where id =  $saf_id ";
        DB::query($sql);
    }
}