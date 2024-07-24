<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";

    public function addDepartment($req)
    {
        $data = new Department();
        $data->department_name = $req->departnameName;
        $data->department_links = $req->link;
        $data->save();
    }

    public function updateDepartment($req)
    {
        $data = Department::where('id', $req->id)
            ->where('status', 1)
            ->first();
        $data->department_name = $req->departnameName;
        $data->department_links = $req->link;
        $data->update();
    }

    public function listDepartment()
    {
        $list = Department::select(
            'id',
            'department_name',
            'department_links',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }

    public function getById($req)
    {
        $list = Department::select(
            'id',
            'department_name',
            'department_links',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }

    public function deleteDepartment($req)
    {
        $userManualHeadingType = Department::find($req->id);
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
