<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AboutUsDetail extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";

    public function addAboutUs($req)
    {
        $data = new AboutUsDetail();
        $data->about_us = $req->aboutUs;
        $data->vision = $req->vision;
        $data->mission = $req->mission;
        $data->objective = $req->objective;
        $data->functn = $req->function;
        $data->save();
    }

    public function updateAboutUs($req)
    {
        $data = AboutUsDetail::where('id', $req->id)
            //->where('status', 1)
            ->first();
        $data->about_us = $req->aboutUs;
        $data->vision = $req->vision;
        $data->mission = $req->mission;
        $data->objective = $req->objective;
        $data->functn = $req->function;
        $data->update();
    }

    public function listAboutUs()
    {
        $list = AboutUsDetail::select(
            'id',
            'about_us',
            'vision',
            'mission',
            'objective',
            'functn',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }

    public function getById($req)
    {
        $list = AboutUsDetail::select(
            'id',
            'about_us',
            'vision',
            'mission',
            'objective',
            'functn',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    public function deleteAboutUs($req)
    {
        $userManualHeadingType = AboutUsDetail::find($req->id);
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

    public function listDash()
    {
        return AboutUsDetail::select(
            '*'
        )->where('status', 1)
            ->orderBy('id', 'asc')
            ->get();
    }
}
