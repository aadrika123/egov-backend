<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MEService extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";

    public function addService($req)
    {
        $data = new MEService();
        $data->service = $req->service;
        $data->service_link = $req->serviceLink;
        $data->save();
    }

    public function listServices()
    {
        $list = MEService::select(
            'id',
            'service',
            'service_link',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }


    public function updateServices($req)
    {
        $data = MEService::where('id', $req->id)
            ->where('status', 1)
            ->first();
        $data->service = $req->service;
        $data->service_link = $req->serviceLink;
        $data->update();
    }

    public function getById($req)
    {
        $list = MEService::select(
            'id',
            'service',
            'service_link',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    public function deleteServices($req)
    {
        $userManualHeadingType = MEService::find($req->id);
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
