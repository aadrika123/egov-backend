<?php

namespace App\Models\water;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class WaterDisconnectionSiteInspections extends WaterParamModel
{
    use HasFactory;

    

    public function setInspectionDate($req)
    {
        $data = [
            "request_id"        =>$req->applicationId,
            "inspection_date"   =>Carbon::parse($req->inspectionDate)->format("Y-m-d"),
            "inspection_time"   =>Carbon::parse($req->inspectionTime)->format("H:i:s"),
        ];
        if($test = self::where("request_id",$req->applicationId)->where("status",1)->where("verify_status",0)->first())
        {
            $update = ["status"=>0];
            self::edit($test->id,$update);
        }
        return self::create($data)->id;

    }

    public function edit($id,array $data)
    {
        return self::where("id",$id)->update($data);
    }

    public function updateInspection($req)
    {        
        $data = [
            "water_site_inspections_id" => $req->waterSiteInspectionsId,
            "property_type_id"      =>  $req->propertyTypeId,
            "pipeline_type_id"      =>  $req->pipelineTypeId,
            "connection_type_id"    =>  $req->connectionTypeId,
            "connection_through"    =>  $req->connectionThrough,
            "category"              =>  $req->category,
            "flat_count"            =>  $req->flatCount,
            "ward_id"               =>  $req->wardId,
            "area_sqft"             =>  $req->areaSqft,
            "rate_id"               =>  $req->rateId,
            "emp_details_id"        =>  $req->userId,
            "pipeline_size"         =>  $req->pipelineSize,
            "pipeline_size_type"    =>  $req->pipelineSizeType,
            "pipe_size"             =>  $req->diameter,
            "ferrule_type"          =>  $req->feruleSize,
            "road_type"             =>  $req->roadType,
            "verified_by"           =>  $req->verifiedBy,
            "ts_map"                =>  $req->tsMap,
            "order_officer"         =>  $req->roleId,
            "pipe_type"             =>  $req->pipeQuality,
            "latitude"              =>  $req->latitude,
            "longitude"             =>  $req->longitude,
            "water_site_inspections_json"=>$req->inspectionsJson,
            "verify_status"=>1
        ];
        return self::where("id",$req->applicationId)->update($data);
    }

    public function getConsumerRequests()
    {
        return $this->belongsTo(WaterConsumerActiveRequest::class,"request_id","id","id")->first();
    }
}
