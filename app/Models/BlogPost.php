<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

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
        $obj->blog_file = $req->asset_path;
        $obj->short_description = $req->shortDescription;
        $obj->long_description = $req->longDescription;
        $obj->by_officer_name = $req->officerName;
        $obj->status = 1;
        $obj->reference_no = $req->ReferenceNo ?? null;
        $obj->unique_id = $req->uniqueId ?? null;

        $obj->save();
        return $obj->id;
    }


    public function allList()
    {
        return self::orderBy("id", "DESC")->get();
    }

    public function edit($req)
    {
        $data = self::where('id', $req->id)->first();
        if (!$data)
            return false;

        $data->title = $req->title ?? $data->title;
        $data->short_description = $req->shortDescription ?? $data->short_description;
        $data->long_description = $req->longDescription ?? $data->long_description;
        $data->by_officer_name = $req->officerName ?? $data->by_officer_name;
        $data->blog_file = $req->document ?? $data->blog_file;
        $data->unique_id = $req->uniqueId ?? $data->unique_id;
        $data->reference_no = $req->ReferenceNo ?? $data->reference_no;

        return $data->update();
    }


    public function deleteBlog($req)
    {
        $blog = self::find($req->id);
        if (!$blog) {
            throw new Exception("Blog post not found");
        }

        $blog->status = $req->status;
        $blog->save();

        return "Blog post status updated";
    }

    public function getById($req)
    {
        return self::where('id', $req->id)->first();
    }



}
