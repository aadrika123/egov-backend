<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UlbMaster extends Model
{
    use HasFactory;
    // use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $guarded = [];

    
    public function store(array $req){
        UlbMaster::create($req);
    }

    public function show(array $req){
        UlbMaster::view($req);
    }

    public function edit(array $req){
        UlbMaster::update($req);
    }

    public function deactivated(array $req){
        UlbMaster::update($req);
    }
}
