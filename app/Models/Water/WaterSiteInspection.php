<?php

namespace App\Models\Water;

use App\Models\Workflows\WfRole;
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
    public function storeInspectionDetails($req, $waterFeeId, $waterDetails, $refRoleDetails)
    {
        $role = WfRole::where('id', $refRoleDetails)->first();
        $saveSiteVerify = new WaterSiteInspection();
        $saveSiteVerify->apply_connection_id    =   $req->applicationId;
        $saveSiteVerify->property_type_id       =   $req->propertyTypeId;
        $saveSiteVerify->pipeline_type_id       =   $req->pipelineTypeId;
        $saveSiteVerify->connection_type_id     =   $req->connectionTypeId;
        $saveSiteVerify->connection_through     =   $waterDetails['connection_through'];
        $saveSiteVerify->category               =   $req->category;
        $saveSiteVerify->flat_count             =   $req->flatCount ?? null;
        $saveSiteVerify->ward_id                =   $waterDetails['ward_id'];
        $saveSiteVerify->area_sqft              =   $req->areaSqFt;
        $saveSiteVerify->rate_id                =   $req->rateId ?? null;                    // what is rate Id
        $saveSiteVerify->emp_details_id         =   authUser()->id;
        $saveSiteVerify->pipeline_size          =   $req->pipelineSize;
        $saveSiteVerify->pipeline_size_type     =   $req->pipelineSizeType;
        $saveSiteVerify->pipe_size              =   $req->diameter;
        $saveSiteVerify->ferrule_type           =   $req->feruleSize;                       // what is ferrule
        $saveSiteVerify->road_type              =   $req->roadType;
        $saveSiteVerify->inspection_date        =   Carbon::now();
        $saveSiteVerify->verified_by            =   $role['role_name'];                     // here role 
        $saveSiteVerify->inspection_time        =   Carbon::now();
        $saveSiteVerify->ts_map                 =   $req->tsMap;
        $saveSiteVerify->order_officer          =   $refRoleDetails;
        $saveSiteVerify->save();
        
        // $saveSiteVerify->scheduled_status       =   $req->scheduledStatus;
        // $saveSiteVerify->water_lock_arng        =   $req->waterLockArng;
        // $saveSiteVerify->gate_valve             =   $req->gateValve;
        // $saveSiteVerify->payment_status         =   $req->paymentStatus;
        // $saveSiteVerify->road_app_fee_id        =   $req->roadAppFeeId;
        // $saveSiteVerify->verified_status        =   true;

    }


    /**
     * | Get Site inspection Details by ApplicationId
     * | According to verification status false
     * | @param applicationId
        | Not Used 
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
            ->where('payment_status', 0)
            ->orderByDesc('water_site_inspections.id');
    }


    /**
     * | Save the Sheduled Date and Time of the Site Inspection
     * | Create a record for further Edit in site inspection
     * | @param request
     | Not used 
     */
    public function saveSiteDateTime($request)
    {
        $inspectionDate = date('Y-m-d', strtotime($request->inspectionDate));
        $mWaterSiteInspection = new WaterSiteInspection();
        $mWaterSiteInspection->apply_connection_id    =   $request->applicationId;
        $mWaterSiteInspection->inspection_date        =   $inspectionDate;
        $mWaterSiteInspection->inspection_time        =   $request->inspectionTime;
        $mWaterSiteInspection->save();
    }
}
