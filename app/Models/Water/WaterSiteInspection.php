<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterSiteInspection extends Model
{
    use HasFactory;

    /**
     * |-------------------- save site inspecton -----------------------\
     * | @param req
     */
    public function store($req)
    {
        $saveSiteVerify = new WaterSiteInspection();
        $saveSiteVerify->property_type_id       =   $req->   ;
        $saveSiteVerify->pipeline_type_id       =   $req->   ;
        $saveSiteVerify->connection_type_id     =   $req->   ;
        $saveSiteVerify->connection_through_id  =   $req->   ;
        $saveSiteVerify->category               =   $req->   ;
        $saveSiteVerify->flat_count             =   $req->   ;
        $saveSiteVerify->ward_id                =   $req->   ;
        $saveSiteVerify->area_sqft              =   $req->   ;
        $saveSiteVerify->rate_id                =   $req->   ;
        $saveSiteVerify->emp_details_id         =   $req->   ;
        $saveSiteVerify->apply_connection_id    =   $req->   ;
        $saveSiteVerify->payment_status         =   $req->   ;
        $saveSiteVerify->pipeline_size          =   $req->   ;
        $saveSiteVerify->pipe_size              =   $req->   ;
        $saveSiteVerify->ferrule_type_id        =   $req->   ;
        $saveSiteVerify->road_type              =   $req->   ;
        $saveSiteVerify->inspection_date        =   $req->   ;
        $saveSiteVerify->scheduled_status       =   $req->   ;
        $saveSiteVerify->water_lock_arng        =   $req->   ;
        $saveSiteVerify->gate_valve             =   $req->   ;
        $saveSiteVerify->verified_by            =   $req->   ;
        $saveSiteVerify->road_app_fee_id        =   $req->   ;
        $saveSiteVerify->verified_status        =   $req->   ;
        $saveSiteVerify->inspection_time        =   $req->   ;
        $saveSiteVerify->ts_map                 =   $req->   ;
        $saveSiteVerify->order_officer          =   $req->   ;
        $saveSiteVerify->save();
    }
}
