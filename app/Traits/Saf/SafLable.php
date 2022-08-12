<?php
namespace App\Traits\Saf;

use App\Models\ActiveSafDetail;
use Illuminate\Support\Facades\DB;

/*
    #traits for comman function Inbox and Outbox of Saf
    * Created On : 11-08-2022 
    * Created by :Sandeep Bara
    #==================================================
*/
trait SafLable
{
    #Inbox
    static public function inbox($saf_id)
    {
        $user_id = auth()->user()->id;
        
        $saf = DB::select(" select string_agg(active_saf_owner_details.owner_name,', ') as owner_name,
                                    string_agg(active_saf_owner_details.guardian_name,', ') as guardian_name ,
                                    string_agg(active_saf_owner_details.mobile_no::text,', ') as mobile_no ,
                                    active_saf_details.id,
                                    active_saf_details.saf_no,
                                    active_saf_details.id,
                                    'SAF' as assesment_type,
                                    'Vacent Lande' as assesment_type
                            from active_saf_details
                            join active_saf_owner_details on  active_saf_owner_details.saf_dtl_id = active_saf_details.id
                            where active_saf_details.current_user = $user_id ".($saf_id?" and active_saf_details.id = $saf_id":"")."
                            group by active_saf_details.id"
                                        );
        if(sizeof($saf)==1)
            return $saf[0];
        return $saf;
    }

    #OutBox
    static public function outbox($saf_id)
    {
        $user_id = auth()->user()->id;
        
        $saf = DB::select(" select string_agg(active_saf_owner_details.owner_name,', ') as owner_name,
                                    string_agg(active_saf_owner_details.guardian_name,', ') as guardian_name ,
                                    string_agg(active_saf_owner_details.mobile_no::text,', ') as mobile_no ,
                                    active_saf_details.id,
                                    active_saf_details.saf_no,
                                    active_saf_details.id,
                                    'SAF' as assesment_type,
                                    'Vacent Lande' as assesment_type
                            from active_saf_details
                            join active_saf_owner_details on  active_saf_owner_details.saf_dtl_id = active_saf_details.id
                            where (active_saf_details.current_user != $user_id or active_saf_details.current_user isnull )".($saf_id?" and active_saf_details.id = $saf_id":"")."
                            group by active_saf_details.id"
                                        );
        if(sizeof($saf)==1)
            return $saf[0];
        return $saf;
    }
}