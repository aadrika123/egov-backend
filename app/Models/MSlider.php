<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MSlider extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";

    //written by prity pandey
    public function addSlider($req)
    {
        $data = new MSlider();
        $data->slider_name = $req->sliderName;
        $data->slider_image_url = $req->sliderImage;
        $data->save();
    }

    public function updateSlider($req)
    {
        $data = MSlider::where('id', $req->id)
            ->where('status', true)
            ->first();
        $data->slider_name = $req->sliderName ??$data->slider_name;
        $data->slider_image_url = $req->sliderImage ??$data->slider_image_url;
        $data->save();
    }


    public function listSlider()
    {
        $list = MSlider::select(
            'id',
            'slider_name',
            'slider_image_url',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }


    public function deleteSlider($req)
    {
        $sliderType = MSlider::find($req->id);
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
}
