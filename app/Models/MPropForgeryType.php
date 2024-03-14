<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPropForgeryType extends Model
{
    use HasFactory;

    protected  $guarded = [];

    protected $hidden = ['created_at', 'updated_at'];
    
    public function show(array $req){
        MPropForgeryType::view($req);
    }

    //written by prity pandey
    public function addForgeryType($req)
    {
        $data = new MPropForgeryType();
        $data->type = $req->Forgerytype;
        $data->save();
    }


    public function updateForgeryType($req)
    {
        $data = MPropForgeryType::where('id', $req->id)
            ->where('status', true)
            ->first();
        $data->type = $req->Forgerytype ??$data->type ;
        $data->save();
    }

    public function getById($req)
    {
        $list = MPropForgeryType::select(
            'id',
            'type',
            'status as is_suspended'
        )
            ->where('id', $req->id)
            ->first();
        return $list;
    }


    public function listForgeryType()
    {
        $list = MPropForgeryType::select(
            'id',
            'type',
            'status as is_suspended'
        )
            ->orderBy('id', 'asc')
            ->get();
        return $list;
    }


    public function deleteForgeryType($req)
    {
        $constructionType = MPropForgeryType::find($req->id);
        $oldStatus = $constructionType->status;
        $constructionType->status = $req->status;
        $constructionType->save();
        if ($oldStatus == 1 && $constructionType->status == 0) {
            $message = "Data Disabled";
        } else {
            $message = "Data Enabled";
        }
        return $message;
    }
}
