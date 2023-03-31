<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropRazorpayRequest extends Model
{
    use HasFactory;
    protected $guarded = [];

    // Store 
    public function store($req)
    {
        PropRazorpayRequest::create($req);
    }
}
