<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CitizenDesk extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";

    public function addCDesk($req)
    {
        $data = new CitizenDesk();
        $data->heading = $req->heading;
        $data->save();
    }

    // public function listUserManualHeading()
    // {
    //     $list = UserManualHeading::select(
    //         'id',
    //         'heading',
    //         'status as is_suspended'
    //     )->join('user_manual_heading_descriptions','user_manual_heading_descriptions.heading_id','=','user_manual_headings.id')
    //         ->orderBy('user_manual_headings.id', 'asc')
    //         ->get();
    //     return $list;
    // }

    public function listUserManualHeading()
    {
        // Fetch user manual headings with associated descriptions
        $list = CitizenDesk::select(
            'user_manual_headings.id',
            'user_manual_headings.heading',
            'user_manual_headings.status as is_suspended',
            'user_manual_heading_descriptions.id as description_id',
            'user_manual_heading_descriptions.description',
            'user_manual_heading_descriptions.user_manual_link',
            'user_manual_heading_descriptions.video_link'
        )
            ->leftjoin('user_manual_heading_descriptions', 'user_manual_heading_descriptions.heading_id', '=', 'user_manual_headings.id')
            ->orderBy('user_manual_headings.id', 'asc')
            ->get()
            ->groupBy('id')
            ->map(function ($item) {
                // Get the first item as the heading details
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
                            'video_link' => $data->video_link
                        ];
                    })->values() // Ensure nested data has numeric indices
                ];
            })
            ->values(); // Ensure the outer collection has numeric indices

        return $list;
    }



    public function updateCDesk($req)
    {
        $data = CitizenDesk::where('id', $req->id)
            ->where('status', 1)
            ->first();
        $data->heading = $req->heading;
        $data->update();
    }

    public function getById($req)
    {
        $list = CitizenDesk::select(
            'id',
            'heading',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    public function deleteCDesk($req)
    {
        $userManualHeadingType = CitizenDesk::find($req->id);
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
