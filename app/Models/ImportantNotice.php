<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportantNotice extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";
    public function addNoticeType($req)
    {
        $data = new ImportantNotice();
        $data->notice = $req->notice;
        $data->save();
    }

    public function listNoticeType()
    {
        $list = ImportantNotice::select(
            'id',
            'notice',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }


    public function updateNoticeType($req)
    {
        $data = ImportantNotice::where('id', $req->id)
            ->where('status', true)
            ->first();
        $data->notice = $req->notice;
        $data->save();
    }

    public function getById($req)
    {
        $list = ImportantNotice::select(
            'id',
            'notice',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    public function deleteNoticeType($req)
    {
        $noticeType = ImportantNotice::find($req->id);
        $oldStatus = $noticeType->status;
        $noticeType->status = $req->status;
        $noticeType->save();
        if ($oldStatus == 1 && $noticeType->status == 0) {
            $message = "Data Disabled";
        } else {
            $message = "Data Enabled";
        }
        return $message;
    }
}
