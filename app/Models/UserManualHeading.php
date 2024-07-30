<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserManualHeading extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";

    public function addHeading($req)
    {
        $data = new UserManualHeading();
        $data->heading = $req->heading;
        $data->save();
    }

    public function listUserManualHeadingMaster()
    {
        $list = UserManualHeading::select(
            'id',
            'heading',
            'status as is_suspended'
        )->where('status', 1)
            ->orderBy('user_manual_headings.id', 'asc')
            ->get();
        return $list;
    }

    public function listUserManualHeadingMasterDesc($headingId)
    {
        $list = UserManualHeading::select(
            'user_manual_headings.id as heading_id',
            'user_manual_headings.heading',
            'user_manual_headings.status as is_suspended',
            'user_manual_heading_descriptions.id as description_id',
            'user_manual_heading_descriptions.description',
            'user_manual_heading_descriptions.user_manual_link',
            'user_manual_heading_descriptions.video_link'
        )->leftjoin('user_manual_heading_descriptions', 'user_manual_heading_descriptions.heading_id', '=', 'user_manual_headings.id')
            ->where('user_manual_headings.status', 1)

            ->where('user_manual_heading_descriptions.status', 1)
            ->where('user_manual_headings.id', $headingId)
            ->orderBy('user_manual_headings.id', 'asc')
            ->get();
        return $list;
    }

    public function listUserManualHeading()
    {
        // Fetch user manual headings with associated descriptions
        $list = UserManualHeading::select(
            'user_manual_headings.id',
            'user_manual_headings.heading',
            'user_manual_headings.status as is_suspended',
            'user_manual_heading_descriptions.id as description_id',
            'user_manual_heading_descriptions.description',
            'user_manual_heading_descriptions.status as is_suspended_v1',
            'user_manual_heading_descriptions.user_manual_link',
            'user_manual_heading_descriptions.video_link'
        )
            ->leftjoin('user_manual_heading_descriptions', 'user_manual_heading_descriptions.heading_id', '=', 'user_manual_headings.id')
            ->orderBy('user_manual_headings.id', 'asc')
            ->get()
            ->groupBy('id')
            ->map(function ($item) {
                $heading = $item->first();
                return [
                    'id' => $heading->id,
                    'heading' => $heading->heading,
                    'is_suspended' => $heading->is_suspended,
                    'data' => $item->map(function ($data) {
                        return [
                            'description_id' => $data->description_id,
                            'description' => $data->description,
                            'user_manual_link' => $data->user_manual_link,
                            'video_link' => $data->video_link,
                            'is_suspended_v1' =>$data->is_suspended_v1
                        ];
                    })->values() 
                ];
            })
            ->values(); 

        return $list;
    }



    public function updateUserManualHeading($req)
    {
        $data = UserManualHeading::where('id', $req->id)
            ->where('status', 1)
            ->first();
        $data->heading = $req->heading;
        $data->update();
    }

    public function getById($req)
    {
        $list = UserManualHeading::select(
            'id',
            'heading',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    public function deleteUserManualHeading($req)
    {
        $userManualHeadingType = UserManualHeading::find($req->id);
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
