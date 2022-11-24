<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropSafVerificationDtl extends Model
{
    use HasFactory;

    // Get Floor Details by Verification Id
    public function getVerificationDetails($verificationId)
    {
        return PropSafVerificationDtl::where('verification_id', $verificationId)->get();
    }
}
