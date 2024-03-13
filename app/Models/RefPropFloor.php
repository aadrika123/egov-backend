<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropFloor extends Model
{
    use HasFactory;

    protected $hidden = ['created_at', 'updated_at'];

    public function show(array $req){
        RefPropFloor::view($req);
    }

    //written by prity pandey
    public function addFloorType($req)
    {
        $data = new RefPropFloor();
        $data->floor_name = $req->floorName;
        $data->save();
    }

     
     public function updatefloorType($req)
     {
         $data = RefPropFloor::where('id', $req->id)
                                        ->where('status', 1)
                                        ->first();
         $data->floor_name = $req->floorName ?? $data->floor_name;
         $data->save();
     }
 
     public function getById($req)
     {
         $list = RefPropFloor::where('id', $req->id)
            // ->where("status",1)
             ->first();
         return $list;
     }
 
     
     public function listFloorType()
     {
         $list = RefPropFloor::select(
             'id',
             'floor_name',
             'status')
             //->where("status",1)
             ->get();
         return $list;
     }
 

     public function deletefloorType($req)
     {
         $floorName = RefPropFloor::find($req->id);
         $oldStatus = $floorName->status;
         $floorName->status = $req->status;
         $floorName->save();
         if ($oldStatus == 1 && $floorName->status ==0) {
            $message = "Data Disabled";
        } else {
            $message = "Data Enabled";
        }
        return $message;
     }
}
