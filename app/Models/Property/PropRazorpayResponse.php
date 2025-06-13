<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropRazorpayResponse extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Store New Razorpay Response
       | Common Function
     */
    public function store(array $req)
    {
        PropRazorpayResponse::create($req);
    }
}
