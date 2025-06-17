<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    protected $fillable = [
        'title',
        'blog_file',
        'short_description',
        'long_description',
        'by_officer_name',
        'status',
        'reference_no',
        'unique_id'
    ];


    public function store($req)
    {
        $obj = new self;
        $obj->title = $req->title;
        $obj->blog_file = $req->blogFile;
        $obj->short_description = $req->shortDescription;
        $obj->long_description = $req->longDescription;
        $obj->by_officer_name = $req->officerName;
        $obj->status = 0;
        $obj->reference_no = $req->ReferenceNo ?? null;
        $obj->unique_id = $req->uniqueId ?? null;

        $obj->save();
        return $obj->id;
    }


    public function allList()
    {
        return self::orderByDesc('id')->get();
    }

    public function edit($req)
    {
        $data = self::where('id', $req->id)->first();
        if (!$data) return false;

        $data->title = $req->title ?? $data->title;
        $data->short_description = $req->shortDescription ?? $data->short_description;
        $data->long_description = $req->longDescription ?? $data->long_description;
        $data->by_officer_name = $req->officerName ?? $data->by_officer_name;
        $data->blog_file = $req->document ?? $data->blog_file;
        $data->unique_id = $req->uniqueId ?? $data->unique_id;
        $data->reference_no = $req->ReferenceNo ?? $data->reference_no;

        return $data->update();
    }



}
