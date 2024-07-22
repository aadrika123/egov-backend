<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserManualHeading extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";

    public function addHeading($req)
    {
        $data = new UserManualHeading();
        $data->heading = $req->heading;
        $data->save();
    }

    public function listUserManualHeading()
    {
        $list = UserManualHeading::select(
            'id',
            'heading',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }


    public function updateUserManualHeading($req)
    {
        $data = UserManualHeading::where('id', $req->id)
            ->where('status',1)
            ->first();
        $data->heading = $req->heading;
        $data->update();
    }

    public function getById($req)
    {
        $list = UserManualHeading::select(
            'id',
            'heading',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    public function deleteUserManualHeading($req)
    {
        $userManualHeadingType = UserManualHeading::find($req->id);
        $oldStatus = $userManualHeadingType->status;
        $userManualHeadingType->status = $req->status;
        $userManualHeadingType->save();
        if ($oldStatus == 1 && $userManualHeadingType->status == 0) {
            $message = "Data Disabled";
        } else {
            $message = "Data Enabled";
        }
        return $message;
    }
}
