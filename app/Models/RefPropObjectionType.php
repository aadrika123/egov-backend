<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropObjectionType extends Model
{
    use HasFactory;

    protected $hidden = ['created_at', 'updated_at', 'delated_at'];

    public function show(array $req)
    {
        RefPropObjectionType::view($req);
    }

    //written by prity pandey
    public function addObjectionType($req)
    {
        $data = new RefPropObjectionType();
        $data->type = $req->Type;
        $data->save();
    }


    public function updateObjectionType($req)
    {
        $data = RefPropObjectionType::where('id', $req->id)
            ->where('status', 1)
            ->first();
        $data->type = $req->Type ?? $data->type;
        $data->save();
    }

    public function getById($req)
    {
        $list = RefPropObjectionType::select(
            'id',
            'type',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }


    public function listObjectionType()
    {
        $list = RefPropObjectionType::select(
            'id',
            'type',
            'status as is_suspended'
        )
            ->get();
        return $list;
    }


    public function deleteObjectionType($req)
    {
        $Type = RefPropObjectionType::find($req->id);
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
