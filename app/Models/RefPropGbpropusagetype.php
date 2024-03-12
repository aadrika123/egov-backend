<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropGbpropusagetype extends Model
{
    use HasFactory;

    protected $hidden = ['created_at', 'updated_at'];

    public function show(array $req){
        RefPropGbpropusagetype::view($req);
    }

    //written by prity pandey
    public function addGbPropUsageType($req)
    {
        $data = new RefPropGbpropusagetype();
        $data->prop_usage_type = $req->propUsageType;
        $data->save();
    }

     
     public function updateGbPropUsageType($req)
     {
         $data = RefPropGbpropusagetype::where('id', $req->id)
                                        ->where('status', 1)
                                        ->first();
         $data->prop_usage_type = $req->propUsageType ?? $data->prop_usage_type;
         $data->save();
     }
 
     public function getById($req)
     {
         $list = RefPropGbpropusagetype::where('id', $req->id)
             ->where("status",1)
             ->first();
         return $list;
     }
 
     
     public function listGbPropUsageType()
     {
         $list = RefPropGbpropusagetype::select(
             'id',
             'prop_usage_type')
             ->where("status",1)
             ->get();
         return $list;
     }
 

     public function deleteGbPropUsageType($req)
     {
         $propUsageType = RefPropGbpropusagetype::find($req->id);
         $oldStatus = $propUsageType->status;
         $propUsageType->status = $req->status;
         $propUsageType->save();
         if ($oldStatus == 1 && $propUsageType->status ==0) {
            $message = "Data Disabled";
        } else {
            $message = "Data Enabled";
        }
        return $message;
     }
    
}
