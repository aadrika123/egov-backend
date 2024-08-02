<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuickLink extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";

    public function addlink($req)
    {
        $data = new QuickLink();
        $data->link_heading = $req->linkHeading;
        $data->quick_link = $req->quickLink;
        $data->save();
    }

    public function updateLink($req)
    {
        $data = QuickLink::where('id', $req->id)
            //->where('status', 1)
            ->first();
        $data->link_heading = $req->linkHeading;
        $data->quick_link = $req->quickLink;
        $data->update();
    }

    public function listQuickLink()
    {
        $list = QuickLink::select(
            'id',
            'link_heading',
            'quick_link',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }

    public function getById($req)
    {
        $list = QuickLink::select(
            'id',
            'link_heading',
            'quick_link',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    public function deleteLink($req)
    {
        $userManualHeadingType = QuickLink::find($req->id);
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
        return QuickLink::select(
            '*'
        )->where('status', 1)
            ->orderBy('id', 'asc')
            ->get();
    }
}
