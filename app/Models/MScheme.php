<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MScheme extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";

    public function addScheme($req)
    {
        $data = new MScheme();
        $data->scheme_name = $req->schemeName;
        $data->scheme_link = $req->schemeLink;
        $data->save();
    }

    public function listScheme()
    {
        $list = MScheme::select(
            'id',
            'scheme_name',
            'scheme_link',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }


    public function updateScheme($req)
    {
        $data = MScheme::where('id', $req->id)
            ->where('status', 1)
            ->first();
        $data->scheme_name = $req->schemeName;
        $data->scheme_link = $req->schemeLink;
        $data->update();
    }

    public function getById($req)
    {
        $list = MScheme::select(
            'id',
            'scheme_name',
            'scheme_link',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    public function deleteScheme($req)
    {
        $userManualHeadingType = MScheme::find($req->id);
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
