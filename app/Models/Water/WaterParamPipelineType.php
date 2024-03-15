<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterParamPipelineType extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_water';

    /**
     * | Get Lsit Of peline type 
     */
    public function getWaterParamPipelineType()
    {
        return WaterParamPipelineType::select(
            'id',
            'pipeline_type'
        )
            ->where('status', true)
            ->get();
    }
    #create 
    public function create($request)
    {
        $data = new WaterParamPipelineType();
        $data->pipeline_type = $request->pipelineType;
        $data->save();
        return $data->id;
    }
    #get data by id
    public function getDataByIdDtls($request)
    {
        return WaterParamPipelineType::select(
            'id',
            'pipeline_type',
            'status as is_suspended'
        )
            ->where('status', 1)
            ->where('id', $request->id)
            ->get();
    }
    #active or inactive
    public function activeDeactiveData($req)
    {
        $data = WaterParamPipelineType::find($req->id);

        if ($req->status == 1) {
            // If status is 1, set status to true (active)
            $data->status = true;
        } else {
            // If status is not 1, set status to false (inactive)
            $data->status = false;
        }
        $data->save();
    }
    # update data by id
    public function updateDataById($request)
    {
        $data =  WaterParamPipelineType::find($request->id);
        $data->pipeline_type = $request->pipelineType;
        $data->save();
        return $data->id;
    }
}
