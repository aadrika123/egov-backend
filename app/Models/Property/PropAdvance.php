<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropAdvance extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Store Function
     */
    public function store($req)
    {
        PropAdvance::create($req);
    }
}
