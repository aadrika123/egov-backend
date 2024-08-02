<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MNewsEvent extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";

    public function addNews($req)
    {
        $data = new MNewsEvent();
        $data->news = $req->news;
        $data->news_link = $req->newsLink;
        $data->save();
    }

    public function listNews()
    {
        $list = MNewsEvent::select(
            'id',
            'news',
            'news_link',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }


    public function updateNews($req)
    {
        $data = MNewsEvent::where('id', $req->id)
            //->where('status', 1)
            ->first();
        $data->news = $req->news;
        $data->news_link = $req->newsLink;
        $data->update();
    }

    public function getById($req)
    {
        $list = MNewsEvent::select(
            'id',
            'news',
            'news_link',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    public function deleteNews($req)
    {
        $userManualHeadingType = MNewsEvent::find($req->id);
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
        return  MNewsEvent::select(
            '*'
        )->where('status', 1)
            ->orderBy('id', 'asc')
            ->get();
    }
}
