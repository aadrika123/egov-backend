<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevDailycollectiondetail extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $connection = 'pgsql_master';

    public function store($req)
    {
        $req = $req->toarray();
        $revDailycollectiondetail =  RevDailycollectiondetail::create($req);
        return $revDailycollectiondetail->id;
    }
}
