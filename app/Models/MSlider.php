<?php

namespace App\Models;

use App\MicroServices\DocUpload;
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
       $data = new self;
       $data->slider_name = $req->sliderName;
       $data->unique_id = $req->uniqueId;
       $data->reference_no = $req->ReferenceNo;
       $data->slider_image_url = $req->sliderImageUrl;
       $data->save();
       return $data->id;
    }

    public function updateSlider($req)
    {
        $data = MSlider::where('id', $req->id)
            ->where('status', true)
            ->first();
        $data->slider_name = $req->sliderName ??$data->slider_name;
        $data->slider_image_url = $req->sliderImageUrl ??$data->slider_image_url;
        $data->reference_no = $req->ReferenceNo ??$data->reference_no;
        $data->unique_id = $req->uniqueId ??$data->unique_id;
        return $data->update();
    }


    public function listSlider()
    {
        $list = MSlider::orderBy('id', 'asc')
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

    public function getById($req)
    {
        $list = MSlider::select(
            'id',
            'slider_name',
            'slider_image_url',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }
}
