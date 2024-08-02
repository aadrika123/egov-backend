<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MAsset extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";


    public function store($req)
    {
        $obj = new self;
        $obj->key = $req->key;
        $obj->asset_file = $req->assetFile;
        $obj->asset_name = $req->assetName;
        $obj->ulb_id = $req->ulbId;
        $obj->unique_id = $req->uniqueId;
        $obj->reference_no = $req->ReferenceNo;
        $obj->save();
        return $obj->id;
    }

    public function edit($req)
    {
        $data = self::where('id', $req->id)
            //->where('status', true)
            ->first();
        $data->key = $req->key ?? $data->key;
        $data->asset_file = $req->assetFile ?? $data->asset_file;
        $data->asset_name = $data->asset_name;
        $data->ulb_id = $req->ulbId ?? $data->ulb_id;
        $data->unique_id = $req->uniqueId ?? $data->unique_id;
        $data->reference_no = $req->ReferenceNo ?? $data->reference_no;
        return $data->update();
    }

    public function allList()
    {
        return self::orderBy("id", "ASC")->get();
    }

    public function deleteAssets($req)
    {
        $assetType = self::find($req->id);
        $oldStatus = $assetType->status;
        $assetType->status = $req->status;
        $assetType->save();
        if ($oldStatus == 1 && $assetType->status == 0) {
            $message = "Data Disabled";
        } else {
            $message = "Data Enabled";
        }
        return $message;
    }

    public function getById($req)
    {
        $list = self::where('id', $req->id)
            ->first();
        return $list;
    }

    public function listDash()
    {
        return self::select('*')
            ->where('status', 1)
            ->orderBy("id", "ASC")
            ->get();
    }
}
