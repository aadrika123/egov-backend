<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropRebateType extends Model
{
    use HasFactory;

    protected $hidden = ['created_at', 'updated_at'];

    //written by prity pandey
    public function addrebatetype($req)
    {
        $user = authUser($req)->id;
        $data = new RefPropRebateType();
        $data->rebate_type = $req->rebateType;
        $data->user_id = $user;
        $data->save();
    }


    public function updaterebatetype($req)
    {
        $data = RefPropRebateType::where('id', $req->id)
            ->where('status', 1)
            ->first();
        $data->rebate_type = $req->rebateType ?? $data->rebate_type;
        $data->save();
    }

    public function getById($req)
    {
        $list = RefPropRebateType::select(
            'id',
            'rebate_type',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }


    public function listrebatetype()
    {
        $list = RefPropRebateType::select(
            'id',
            'rebate_type',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }


    public function deletepropertytype($req)
    {
        $Type = RefPropRebateType::find($req->id);
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
