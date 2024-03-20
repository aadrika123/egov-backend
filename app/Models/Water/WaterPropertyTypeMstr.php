<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterPropertyTypeMstr extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_water';

    /**
     * | Get Property type Details
     */
    public function getWaterPropertyTypeMstr()
    {
        return WaterPropertyTypeMstr::select(
            'id',
            'property_type',
            'status as is_suspended'
        )
            ->where('status', 1)
            ->get();
    }
    #create 
    public function create($request)
    {
        $data = new WaterPropertyTypeMstr();
        $data->property_type = $request->propertyType;
        $data->save();
        return $data->id;
    }
    #get data by id
    public function getDataByIdDtls($request)
    {
        return WaterPropertyTypeMstr::select(
            'id',
            'property_type',
            'status as is_suspended'
        )
            ->where('status', 1)
            ->where('id', $request->id)
            ->get();
    }
    #active or inactive
    public function activeDeactiveData($req)
    {
        $data = WaterPropertyTypeMstr::find($req->id);

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
        $data =  WaterPropertyTypeMstr::find($request->id);
        $data->property_type = $request->propertyType;
        $data->save();
        return $data->id;
    }
}
