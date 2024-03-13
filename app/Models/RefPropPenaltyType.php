<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropPenaltyType extends Model
{
    use HasFactory;

    protected $hidden = ['created_at', 'updated_at'];

    public function metaData(array $data)
    {
        $metaData=[
            "penalty_type" => $data["penaltyType"],
            "user_id" => $data["userId"],
            
        ];
        isset($data["status"]) ? $metaData["status"] = $data["status"]:"";
        return $metaData;
    }

    public function store(array $data)
    {
        if($test = self::where("penalty_type",$data["penaltyType"])->fist())
        {
            $data["status"]=1;
            return $this->edit($test->id,$data);
        }
        return self::create($this->metaData($data))->id;
    }

    public function edit($id,array $data)
    {
        return self::where("id",$id)->update($this->metaData($data));
    }

    //written by prity pandey
    public function addpenaltytype($req)
    {
        $user = authUser($req)->id;
        $data = new RefPropPenaltyType();
        $data->penalty_type = $req->penaltyType;
        $data->user_id = $user;
        $data->save();
    }

    
    public function updatepenaltytype($req)
    {
        $data = RefPropPenaltyType::where('id', $req->id)
                                        ->where('status', 1)
                                        ->first();
        $data->penalty_type = $req->penaltyType ?? $data->penalty_type;
        $data->save();
    }

    public function getById($req)
    {
        $list = RefPropPenaltyType::where('id', $req->id)
           // ->where("status",1)
            ->first();
        return $list;
    }

    
    public function listpenaltytype()
    {
        $list = RefPropPenaltyType::select(
            'id',
            'penalty_type',
            'status as is_suspended')
           // ->where("status",1)
            ->get();
        return $list;
    }


    public function deletepenaltytype($req)
    {
        $Type = RefPropPenaltyType::find($req->id);
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
