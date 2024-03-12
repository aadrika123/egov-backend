<?php

namespace App\Models;

use GuzzleHttp\Psr7\Message;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropConstructionType extends Model
{
    use HasFactory;

    
    protected  $guarded = [];

    protected $hidden = ['created_at', 'updated_at'];
    
    public function show(array $req){
        RefPropConstructionType::view($req);
    }

    //written by prity pandey
    public function addConstructionType($req)
    {
        $data = new RefPropConstructionType();
        $data->construction_type = $req->constructionType;
        $data->save();
    }

     
     public function updateConstructionType($req)
     {
         $data = RefPropConstructionType::where('id', $req->id)
                                        ->where('status', 1)
                                        ->first();
         $data->construction_type = $req->constructionType ?? $data->construction_type;
         $data->save();
     }
 
     public function getById($req)
     {
         $list = RefPropConstructionType::where('id', $req->id)
             ->where("status",1)
             ->first();
         return $list;
     }
 
     
     public function listConstructionType()
     {
         $list = RefPropConstructionType::select(
             'id',
             'construction_type')
             ->where("status",1)
             ->get();
         return $list;
     }
 

     public function deleteConstructionType($req)
     {
         $constructionType = RefPropConstructionType::find($req->id);
         $oldStatus = $constructionType->status;
         $constructionType->status = $req->status;
         $constructionType->save();
         if ($oldStatus == 1 && $constructionType->status ==0) {
            $message = "Data Disabled";
        } else {
            $message = "Data Enabled";
        }
        return $message;
     }
}
