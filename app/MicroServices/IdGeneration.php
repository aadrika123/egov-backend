<?php

namespace App\MicroServices;

use App\Models\Masters\IdGenerationParam;
use App\Models\UlbMaster;
use Carbon\Carbon;

/**
 * | Created On-16-01-2023 
 * | Created By-Anshu Kumar
 * | Created for Id Generation MicroService
 */
class IdGeneration
{
    /**
     * | Generate Transaction ID
     */
    public function generateTransactionNo()
    {
        return Carbon::createFromDate()->milli . carbon::now()->diffInMicroseconds() . Carbon::now()->format('Y');
    }

    /**
     * | Generate Random OTP 
     */
    public function generateOtp()
    {
        // $otp = Carbon::createFromDate()->milli . random_int(100, 999);
        $otp = 123123;
        return $otp;
    }
}
