<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportantLink extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";

    public function addlink($req)
    {
        $data = new ImportantLink();
        $data->link_heading = $req->linkHeading;
        $data->links = $req->importantLink;
        $data->save();
    }

    public function updateLink($req)
    {
        $data = ImportantLink::where('id', $req->id)
           // ->where('status', 1)
            ->first();
        $data->link_heading = $req->linkHeading;
        $data->links = $req->importantLink;
        $data->update();
    }

    public function listImportantLink()
    {
        $list = ImportantLink::select(
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
        $list = ImportantLink::select(
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
        $userManualHeadingType = ImportantLink::find($req->id);
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
        return ImportantLink::select(
            '*'
        )->where('status', 1)
            ->orderBy('id', 'asc')
            ->get();
    }
}
