<?php

namespace App\Models;

use App\Repository\Auth\EloquentAuthRepository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class ActiveCitizen extends Model
{
    use HasFactory, HasApiTokens;

    protected $guarded = [];
    // Citizen Registration
    public function citizenRegister($request)
    {
        $reqs = [
            'user_name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'password' => Hash::make($request->password),
            'gender' => $request->gender,
            'dob'    => $request->dob,
            'aadhar' => $request->aadhar,
            'aadhar_doc' => $request->aadharDoc,
            'is_specially_abled' => $request->isSpeciallyAbled,
            'specially_abled_doc' => $request->speciallAbledDoc,
            'is_armed_force' => $request->isArmedForce,
            'armed_force_doc' => $request->armedForceDoc,
            'ip_address' => getClientIpAddress()

        ];

        ActiveCitizen::create($reqs);
    }

    /**
     * | Get Active Citizens by Moble No
     */
    public function getCitizenByMobile($mobile)
    {
        return ActiveCitizen::where('mobile', $mobile)
            ->first();
    }
}
