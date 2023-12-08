<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataMap extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function store($req)
    {
        $data = DataMap::create($req);
        return $data;
    }
}
