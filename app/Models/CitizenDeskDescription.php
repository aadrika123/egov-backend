<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CitizenDeskDescription extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";

    public function addCDeskDes($req)
    {
        $data = new CitizenDeskDescription();
        $data->heading = $req->headingDesc;
        $data->links = $req->link;
        $data->desk_id = $req->deskId;
        $data->save();
    }

    public function updateCDeskDesc($req)
    {
        $data = CitizenDeskDescription::where('id', $req->id)
            //->where('status', 1)
            ->first();
        $data->heading = $req->headingDesc;
        $data->links = $req->link;
        $data->desk_id = $req->deskId;
        $data->update();
    }

    public function getById($req)
    {
        $list = CitizenDeskDescription::select(
            'id',
            'heading',
            'links',
            'desk_id',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    public function deleteCDeskDesc($req)
    {
        $userManualHeadingType = CitizenDeskDescription::find($req->id);
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
