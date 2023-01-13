<?php

namespace App\Models;

use App\Repository\Auth\EloquentAuthRepository;
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
            'user_name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'password' => Hash::make($request->password)
        ];

        ActiveCitizen::create($reqs);
        $mUser = new User();
        $mUser->user_name = $request->name;
        $mUser->email = $request->email;
        $mUser->mobile = $request->mobile;
        $mUser->ulb_id = $request->ulb;
        $mUser->password = Hash::make($request->password);
        $mUser->save();
    }
}
