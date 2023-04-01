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
        $stored = PropRazorpayRequest::create($req);
        return [
            'razorPayReqId' => $stored->id
        ];
    }
}
