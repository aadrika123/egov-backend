<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MMobileAppLink extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";

    public function addMApp($req)
    {
        $data = new MMobileAppLink();
        $data->app_name = $req->appName;
        $data->app_link = $req->appLink;
        $data->save();
    }

    public function listMApp()
    {
        $list = MMobileAppLink::select(
            'id',
            'app_name',
            'app_link',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }


    public function updateMApp($req)
    {
        $data = MMobileAppLink::where('id', $req->id)
            ->where('status', 1)
            ->first();
        $data->app_name = $req->appName;
        $data->app_link = $req->appLink;
        $data->update();
    }

    public function getById($req)
    {
        $list = MMobileAppLink::select(
            'id',
            'app_name',
            'app_link',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    public function deleteMApp($req)
    {
        $userManualHeadingType = MMobileAppLink::find($req->id);
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
