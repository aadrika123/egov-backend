<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropOccupancyFactor extends Model
{
    use HasFactory;

    protected $hidden = ['created_at', 'updated_at'];

    public function show($req)
    {
        RefPropOccupancyType::view($req);
    }

    //written by prity pandey
    public function addOccupancyFactor($req)
    {
        $data = new RefPropOccupancyFactor();
        $data->occupancy_name = $req->occupancyName;
        $data->mult_factor = $req->multFactor;
        $data->save();
    }


    public function updateOccupancyFactor($req)
    {
        $data = RefPropOccupancyFactor::where('id', $req->id)
            ->where('status', 1)
            ->first();
        $data->occupancy_name = $req->occupancyName ?? $data->occupancy_name;
        $data->mult_factor = $req->multFactor ?? $data->mult_factor;
        $data->save();
    }

    public function getById($req)
    {
        $list = RefPropOccupancyFactor::select(
            'id',
            'occupancy_name',
            'mult_factor',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }


    public function listOccupancyFactor()
    {
        $list = RefPropOccupancyFactor::select(
            'id',
            'occupancy_name',
            'mult_factor',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }


    public function deleteOccupancyFactor($req)
    {
        $Type = RefPropOccupancyFactor::find($req->id);
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
