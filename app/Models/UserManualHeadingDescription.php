<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserManualHeadingDescription extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";

    public function addHeadingDes($req)
    {
        $data = new UserManualHeadingDescription();
        $data->heading_id = $req->headingId;
        $data->description = $req->description;
        $data->video_link = $req->videoLink;
        $data->user_manual_link = $req->userManualLink;
        $data->save();
    }

    public function listUserManualHeading()
    {
        $list = UserManualHeadingDescription::select(
            'user_manual_heading_descriptions.id',
            'user_manual_headings.heading',
            'user_manual_heading_descriptions.description',
            'user_manual_heading_descriptions.video_link',
            'user_manual_heading_descriptions.user_manual_link',
            'user_manual_heading_descriptions.status as is_suspended'
        )
            ->join('user_manual_headings', 'user_manual_headings.id', '=', 'user_manual_heading_descriptions.heading_id')
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }

    public function updateUserManualHeading($req)
    {
        $data = UserManualHeadingDescription::where('id', $req->id)
            ->where('status', 1)
            ->first();
        $data->heading_id = $req->headingId;
        $data->description = $req->description;
        $data->video_link = $req->videoLink;
        $data->user_manual_link = $req->userManualLink;
        $data->save();
    }

    public function getById($req)
    {
        $list = UserManualHeadingDescription::select(
            'user_manual_heading_descriptions.id',
            'user_manual_headings.heading',
            'user_manual_heading_descriptions.description',
            'user_manual_heading_descriptions.video_link',
            'user_manual_heading_descriptions.user_manual_link',
            'user_manual_heading_descriptions.status as is_suspended'
        )->join('user_manual_headings', 'user_manual_headings.id', '=', 'user_manual_heading_descriptions.heading_id')
            ->where('user_manual_heading_descriptions.id', $req->id)
            ->first();
        return $list;
    }

    public function deleteUserHeading($req)
    {
        $userManualHeadingType = UserManualHeadingDescription::find($req->id);
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
