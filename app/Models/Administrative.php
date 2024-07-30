<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Administrative extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";
    public function addAdministrative($req)

    {
        $data = new self;
        $data->name = $req->Name;
        $data->unique_id = $req->uniqueId;
        $data->reference_no = $req->ReferenceNo;
        $data->image_url = $req->Image;
        $data->designation = $req->designation;
        $data->address = $req->address;
        $data->phone = $req->phone;
        $data->email = $req->email;
        $data->save();
        return $data->id;
    }

    public function updateAdministrative($req)
    {
        $data = self::where('id', $req->id)
            ->where('status', true)
            ->first();
        $data->name = $req->Name ?? $data->name;
        $data->image_url = $req->Image ?? $data->image_url;
        $data->reference_no = $req->ReferenceNo ?? $data->reference_no;
        $data->unique_id = $req->uniqueId ?? $data->unique_id;
        $data->designation = $req->designation ??  $data->designation;
        $data->address = $req->address ??$data->address ;
        $data->phone = $req->phone ?? $data->phone;
        $data->email = $req->email ??$data->email ;
        return $data->update();
    }

    public function listRule()
    {
        $list = self::orderBy('id', 'asc')
            ->get();
        return $list;
    }

    public function deleteAdministrative($req)
    {
        $sliderType = self::find($req->id);
        $oldStatus = $sliderType->status;
        $sliderType->status = $req->status;
        $sliderType->save();
        if ($oldStatus == 1 && $sliderType->status == 0) {
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
            ->orderBy('id', 'asc')
            ->get();
    }
}
