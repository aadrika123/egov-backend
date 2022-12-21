<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class ActiveCitizen extends Model
{
    use HasFactory;

    protected $guarded = [];
    // Citizen Registration
    public function citizenRegister($request)
    {
        $reqs = [
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'password' => Hash::make($request->password)
        ];

        ActiveCitizen::create($reqs);
    }
}
