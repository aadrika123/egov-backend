<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MWhat extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";

    public function addWhatsNew($req)
    {
        $data = new MWhat();
        $data->whats_new = $req->whatsNew;
        $data->save();
    }

    public function updateWhatNew($req)
    {
        $data = MWhat::where('id', $req->id)
            ->where('status', 1)
            ->first();
        $data->whats_new = $req->whatsNew;
        $data->update();
    }

    public function listWhatNew()
    {
        $list = MWhat::select(
            'id',
            'whats_new',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }

    public function getById($req)
    {
        $list = MWhat::select(
            'id',
            'whats_new',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    public function deleteWhatNew($req)
    {
        $userManualHeadingType = MWhat::find($req->id);
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
