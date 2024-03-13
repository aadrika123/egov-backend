<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropRoadType extends Model
{
    use HasFactory;

    //written by prity pandey
    public function addroadtype($req)
    {
        $data = new RefPropRoadType();
        $data->road_type = $req->roadType;
        $data->save();
    }


    public function updateroadtype($req)
    {
        $data = RefPropRoadType::where('id', $req->id)
            ->where('status', 1)
            ->first();
        $data->road_type = $req->roadType ?? $data->road_type;
        $data->save();
    }

    public function getById($req)
    {
        $list = RefPropRoadType::select(
            'id',
            'road_type',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }


    public function listroadtype()
    {
        $list = RefPropRoadType::select(
            'id',
            'road_type',
            'status as is_suspended'
        )
            ->get();
        return $list;
    }


    public function deleteroadtype($req)
    {
        $Type = RefPropRoadType::find($req->id);
        $oldStatus = $Type->status;
        $Type->status = $req->status;
        $Type->save();
        if ($oldStatus == 1 && $Type->status == 0) {
            $message = "Data Disabled";
        } else {
            $message = "Data Enabled";
        }
        return $message;
    }
}
