<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MPropForgeryType extends Model
{
    use HasFactory;

    /**
     * | Get Forgery Type by ID
       | Reference Function : forgeryType()
     */
    public function forgeryType()
    {
        return MPropForgeryType::select('id', 'type')
            ->where('status', true)
            ->orderby('id')
            ->get();
    }

    //written by prity pandey

    /**
     * | Get Forgery Type by ID
       | Reference Function : createForgeryType
     */
    public function addForgeryType($req)
    {
        $data = new MPropForgeryType();
        $data->type = $req->Forgerytype;
        $data->save();
    }

    /**
     * | Update Forgery Type by ID
       | Reference Function : updateForgeryType
     */
    public function updateForgeryType($req)
    {
        $data = MPropForgeryType::where('id', $req->id)
            ->where('status', true)
            ->first();
        $data->type = $req->Forgerytype ??$data->type ;
        $data->save();
    }

    /**
     * | Get Forgery Type by ID
       | Reference Function : ForgeryTypebyId
     */
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

    /**
     * | Get Forgery Type by ULB ID
       | Reference Function : allForgeryTypelist
     */
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

    /**
     * | Delete Forgery Type by ID
       | Reference Function : deleteForgeryType
     */
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
