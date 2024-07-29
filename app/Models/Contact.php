<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";

    public function addContact($req)
    {
        $data = new Contact();
        $data->department_name = $req->departnameName;
        $data->address = $req->address;
        $data->mobile_no = $req->mobile;
        $data->email = $req->email;
        $data->fax = $req->fax;
        $data->save();
    }

    public function updateContact($req)
    {
        $data = self::where('id', $req->id)
            ->where('status', 1)
            ->first();
        $data->department_name = $req->departnameName;
        $data->address = $req->address;
        $data->mobile_no = $req->mobile;
        $data->email = $req->email;
        $data->fax = $req->fax;
        $data->update();
    }

    public function listContact()
    {
        $list = Contact::select(
            'id',
            'department_name',
            'address',
            'mobile_no',
            'email',
            'fax',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }

    public function getById($req)
    {
        $list = Contact::select(
            'id',
            'department_name',
            'address',
            'mobile_no',
            'email',
            'fax',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    public function deleteContact($req)
    {
        $userManualHeadingType = Contact::find($req->id);
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
        return Contact::select(
            '*'
        )->where('status', 1)
            ->orderBy('id', 'asc')
            ->get();
    }
}
