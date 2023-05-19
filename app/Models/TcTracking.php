<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TcTracking extends Model
{
    use HasFactory;
    protected $fillable = ['lattitude', 'longitude', 'user_id'];
    public $timestamps = false;

    public function store($req)
    {
        $req = $req->toarray();
        TcTracking::create($req);
    }
}
