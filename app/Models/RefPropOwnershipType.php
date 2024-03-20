<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropOwnershipType extends Model
{
    use HasFactory;

    protected $hidden = ['created_at', 'updated_at'];

    public function show(array $req)
    {
        RefPropOwnershipType::view($req);
    }

    //written by prity pandey
    public function addOwnershiptype($req)
    {
        $data = new RefPropOwnershipType();
        $data->ownership_type = $req->ownershipType;
        $data->save();
    }


    public function updateOwnershiptype($req)
    {
        $data = RefPropOwnershipType::where('id', $req->id)
            ->where('status', 1)
            ->first();
        $data->ownership_type = $req->ownershipType ?? $data->ownership_type;
        $data->save();
    }

    public function getById($req)
    {
        $list = RefPropOwnershipType::select(
            'id',
            'ownership_type',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }


    public function listOwnershiptype()
    {
        $list = RefPropOwnershipType::select(
            'id',
            'ownership_type',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }


    public function deleteOwnershiptype($req)
    {
        $Type = RefPropOwnershipType::find($req->id);
        $oldStatus = $Type->status;
        $Type->status = $req->status;
        $Type->save();
        if ($oldStatus == 1 && $Type->status == 0) {
            $message = "Data Disabled";
        } else {
            $message = "Data Enabled";
        }
        return $message;
    }
}
