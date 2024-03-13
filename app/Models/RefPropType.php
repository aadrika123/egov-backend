<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropType extends Model
{
    use HasFactory;

    protected $hidden = ['created_at', 'updated_at'];

    //written by prity pandey
    public function addpropertytype($req)
    {
        //$user = Auth()->user()->id;
        $data = new RefPropType();
        $data->property_type = $req->propertyType;
        //$data->user_id = $user;
        $data->save();
    }

    
    public function updatepropertytype($req)
    {
        $data = RefPropType::where('id', $req->id)
                                        ->where('status', 1)
                                        ->first();
        $data->property_type = $req->propertyType ?? $data->property_type;
        $data->save();
    }

    public function getById($req)
    {
        $list = RefPropType::where('id', $req->id)
           // ->where("status",1)
            ->first();
        return $list;
    }

    
    public function listpropertytype()
    {
        $list = RefPropType::select(
            'id',
            'property_type',
            'status')
           // ->where("status",1)
            ->get();
        return $list;
    }


    public function deletepropertytype($req)
    {
        $Type = RefPropType::find($req->id);
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
