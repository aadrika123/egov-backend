<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsefulLink extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $connection = "pgsql_master";

    public function addlink($req)
    {
        $data = new UsefulLink();
        $data->link_heading = $req->linkHeading;
        $data->links = $req->usefulLink;
        $data->save();
    }

    public function updateLink($req)
    {
        $data = UsefulLink::where('id', $req->id)
            ->where('status', 1)
            ->first();
        $data->link_heading = $req->linkHeading;
        $data->links = $req->usefulLink;
        $data->update();
    }

    public function listUsefulLink()
    {
        $list = UsefulLink::select(
            'id',
            'link_heading',
            'links',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }

    public function getById($req)
    {
        $list = UsefulLink::select(
            'id',
           'link_heading',
            'links',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    public function deleteLink($req)
    {
        $userManualHeadingType = UsefulLink::find($req->id);
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
