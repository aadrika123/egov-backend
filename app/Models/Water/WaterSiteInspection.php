<?php

namespace App\Models\Water;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterSiteInspection extends Model
{
    use HasFactory;

    /**
     * |-------------------- save site inspecton -----------------------\
     * | @param req
     */
    public function storeInspectionDetails($req)
    {
        $saveSiteVerify = new WaterSiteInspection();
        $saveSiteVerify->property_type_id       =   $req->propertyType;
        $saveSiteVerify->pipeline_type_id       =   $req->pipelineType;
        $saveSiteVerify->connection_type_id     =   $req->connectionType;
        $saveSiteVerify->connection_through_id  =   $req->connectionThrough;
        $saveSiteVerify->category               =   $req->category;
        $saveSiteVerify->flat_count             =   $req->flatCount;
        $saveSiteVerify->ward_id                =   $req->wardId;
        $saveSiteVerify->area_sqft              =   $req->areaSqft;
        $saveSiteVerify->rate_id                =   $req->rateId;                    // what is rate Id
        $saveSiteVerify->emp_details_id         =   $req->empDetailsId;
        $saveSiteVerify->apply_connection_id    =   $req->applyConnectionId;
        $saveSiteVerify->payment_status         =   $req->paymentStatus;
        $saveSiteVerify->pipeline_size          =   $req->pipelineSize;
        $saveSiteVerify->pipe_size              =   $req->pipeSize;
        $saveSiteVerify->ferrule_type_id        =   $req->ferrule_type_id;           // what is ferrule
        $saveSiteVerify->road_type              =   $req->roadType;
        $saveSiteVerify->inspection_date        =   Carbon::now('y-m-d');
        $saveSiteVerify->scheduled_status       =   $req->scheduledStatus;
        $saveSiteVerify->water_lock_arng        =   $req->waterLockArng;
        $saveSiteVerify->gate_valve             =   $req->gateValve;
        $saveSiteVerify->verified_by            =   $req->verifiedBy;
        $saveSiteVerify->road_app_fee_id        =   $req->roadAppFeeId;
        $saveSiteVerify->verified_status        =   $req->verificationStatus;
        $saveSiteVerify->inspection_time        =   $req->inspectionTime;
        $saveSiteVerify->ts_map                 =   $req->tsMap;
        $saveSiteVerify->order_officer          =   $req->orderOfficer;
        $saveSiteVerify->save();
    }

    /**
     * | Get Site inspection Details by ApplicationId
     * | According to verification status false
     * | @param applicationId
     */
    public function getInspectionById($applicationId)
    {
        return WaterSiteInspection::select(
            'water_site_inspections.*',

            'id as site_inspection_id',
            'property_type_id as site_inspection_property_type_id',
            'area_sqft as site_inspection_area_sqft'
        )
            ->where('apply_connection_id', $applicationId)
            ->where('status', true)
            ->where('payment_status', 0);
    }
}
