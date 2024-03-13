<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropOccupancyType extends Model
{
    use HasFactory;

    //written by prity pandey
    public function addOccupancytype($req)
    {
        $data = new RefPropOccupancyType();
        $data->occupancy_type = $req->occupancyType;
        $data->save();
    }

    
    public function updateOccupancytype($req)
    {
        $data = RefPropOccupancyType::where('id', $req->id)
                                        ->where('status', 1)
                                        ->first();
        $data->occupancy_type = $req->occupancyType ?? $data->occupancy_type;
        $data->save();
    }

    public function getById($req)
    {
        $list = RefPropOccupancyType::where('id', $req->id)
           // ->where("status",1)
            ->first();
        return $list;
    }

    
    public function listOccupancytype()
    {
        $list = RefPropOccupancyType::select(
            'id',
            'occupancy_type',
            'status as is_suspended')
            //->where("status",1)
            ->get();
        return $list;
    }


    public function deleteOccupancytype($req)
    {
        $Type = RefPropOccupancyType::find($req->id);
        $oldStatus = $Type->status;
        $Type->status = $req->status;
        $Type->save();
        if ($oldStatus == 1 && $Type->status ==0) {
            $message = "Data Disabled";
        } else {
            $message = "Data Enabled";
        }
        return $message;
    }

}
