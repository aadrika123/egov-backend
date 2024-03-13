<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropGbbuildingusagetype extends Model
{
    use HasFactory;

    protected $hidden = ['created_at', 'updated_at'];

    public function show(array $req){
        RefPropGbbuildingusagetype::view($req);
    }

    //written by prity pandey
    public function addGbBuildingType($req)
    {
        $data = new RefPropGbbuildingusagetype();
        $data->building_type = $req->buildingType;
        $data->save();
    }

     
     public function updateGbBuildingType($req)
     {
         $data = RefPropGbbuildingusagetype::where('id', $req->id)
                                        ->where('status', 1)
                                        ->first();
         $data->building_type = $req->buildingType ?? $data->building_type;
         $data->save();
     }
 
     public function getById($req)
     {
         $list = RefPropGbbuildingusagetype::where('id', $req->id)
             //->where("status",1)
             ->first();
         return $list;
     }
 
     
     public function listGbBuildingType()
     {
         $list = RefPropGbbuildingusagetype::select(
             'id',
             'building_type',
             'status')
             //->where("status",1)
             ->get();
         return $list;
     }
 

     public function deleteGbBuildingType($req)
     {
         $buildingType = RefPropGbbuildingusagetype::find($req->id);
         $oldStatus = $buildingType->status;
         $buildingType->status = $req->status;
         $buildingType->save();
         if ($oldStatus == 1 && $buildingType->status ==0) {
            $message = "Data Disabled";
        } else {
            $message = "Data Enabled";
        }
        return $message;
     }
}
