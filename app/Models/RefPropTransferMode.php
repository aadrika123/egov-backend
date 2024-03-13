<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropTransferMode extends Model
{
    use HasFactory;

    //written by prity pandey
    public function addproptransfermode($req)
    {
        //$user = Auth()->user()->id;
        $data = new RefPropTransferMode();
        $data->transfer_mode = $req->transferMode;
        //$data->user_id = $user;
        $data->save();
    }

    
    public function updateproptransfermode($req)
    {
        $data = RefPropTransferMode::where('id', $req->id)
                                        ->where('status', 1)
                                        ->first();
        $data->transfer_mode = $req->transferMode ?? $data->transfer_mode;
        $data->save();
    }

    public function getById($req)
    {
        $list = RefPropTransferMode::where('id', $req->id)
            //->where("status",1)
            ->first();
        return $list;
    }

    
    public function listproptransfermode()
    {
        $list = RefPropTransferMode::select(
            'id',
            'transfer_mode',
            'status as is_suspended')
            //->where("status",1)
            ->get();
        return $list;
    }


    public function deleteproptransfermode($req)
    {
        $Type = RefPropTransferMode::find($req->id);
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
