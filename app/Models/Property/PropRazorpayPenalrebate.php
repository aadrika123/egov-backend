<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropRazorpayPenalrebate extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Store New Penalty Rebates
     */
    public function store(array $req)
    {
        PropRazorpayPenalrebate::create($req);
    }
}
