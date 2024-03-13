<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropUsageType extends Model
{
    use HasFactory;

    protected $hidden = ['created_at', 'updated_at'];

     //written by prity pandey
     public function addpropusagetype($req)
     {
         //$user = Auth()->user()->id;
         $data = new RefPropUsageType();
         $data->usage_type = $req->usageType;
         $data->usage_code = $req->usageCode;
         //$data->user_id = $user;
         $data->save();
     }
 
     
     public function updatepropusagetype($req)
     {
         $data = RefPropUsageType::where('id', $req->id)
                                         ->where('status', 1)
                                         ->first();
         $data->usage_type = $req->usageType ?? $data->usage_type;
         $data->usage_code = $req->usageCode ?? $data->usage_code;
         $data->save();
     }
 
     public function getById($req)
     {
         $list = RefPropUsageType::where('id', $req->id)
            // ->where("status",1)
             ->first();
         return $list;
     }
 
     
     public function listpropusagetype()
     {
         $list = RefPropUsageType::select(
             'id',
             'usage_type',
             'usage_code',
             'status')
             //->where("status",1)
             ->get();
         return $list;
     }
 
 
     public function deletepropusagetype($req)
     {
         $Type = RefPropUsageType::find($req->id);
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
