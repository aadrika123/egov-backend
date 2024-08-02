<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";
    public function addAnnouncementType($req)
    {
        $data = new Announcement();
        $data->announcement = $req->announcement;
        $data->save();
    }

    public function listAnnouncementType()
    {
        $list = Announcement::select(
            'id',
            'announcement',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }


    public function updateAnnouncementType($req)
    {
        $data = Announcement::where('id', $req->id)
            //->where('status', true)
            ->first();
        $data->announcement = $req->announcement;
        $data->save();
    }

    public function getById($req)
    {
        $list = Announcement::select(
            'id',
            'announcement',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    public function deleteAnnouncementType($req)
    {
        $announcementType = Announcement::find($req->id);
        $oldStatus = $announcementType->status;
        $announcementType->status = $req->status;
        $announcementType->save();
        if ($oldStatus == 1 && $announcementType->status == 0) {
            $message = "Data Disabled";
        } else {
            $message = "Data Enabled";
        }
        return $message;
    }

    public function listDash()
    {
        return Announcement::select(
            '*'
        )
            ->where('status', 1)
            ->orderBy('id', 'asc')
            ->get();
    }
}
