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
     * | @param paramId Module Parameter Id For The Module
     * | @param incrementStatus Status to Return Value with True or False
     */
    public function generateId($paramId, $incrementStatus)
    {
        $mIdParam = new IdGenerationParam();
        $params = $mIdParam->getParams($paramId);

        $stringVal = $params->string_val;
        $intVal = $params->int_val;
        // Case for the Increamental
        if ($incrementStatus == true) {
            $id = $stringVal . '/' . str_pad($intVal, 6, "0", STR_PAD_LEFT);
            $intVal += 1;
            $params->int_val = $intVal;
            $params->save();
        }

        // Case for not Increamental
        if ($incrementStatus == false) {
            $id = $stringVal . '/' . str_pad($intVal, 6, "0", STR_PAD_LEFT);
        }
        return $id;
    }

    /**
     * | Generate PT(Property Tax) no
     */
    public function generatePtNo($incrementStatus, $ulbId)
    {
        $mIdParam = new IdGenerationParam();
        $mUlbMaster = new UlbMaster();
        $ulbDtls = $mUlbMaster::findOrFail($ulbId);

        $ulbDistrictCode = $ulbDtls->district_code;
        $ulbCategory = $ulbDtls->category;
        $code = $ulbDtls->code;

        $paramId = 3;                       // Pt String Id
        $params = $mIdParam->getParams($paramId);
        $prefixString = $params->string_val;
        $stringVal = $prefixString . '/' . $ulbDistrictCode . $ulbCategory . $code;

        $stringSplit = collect(str_split($stringVal));
        $flag = ($stringSplit->sum()) % 9;
        $intVal = $params->int_val;
        // Case for the Increamental
        if ($incrementStatus == true) {
            $id = $stringVal . str_pad($intVal, 7, "0", STR_PAD_LEFT);
            $intVal += 1;
            $params->int_val = $intVal;
            $params->save();
        }

        // Case for not Increamental
        if ($incrementStatus == false) {
            $id = $stringVal  . str_pad($intVal, 7, "0", STR_PAD_LEFT);
        }
        return $id . $flag;
    }

    /**
     * | Generate Transaction ID
     */
    public function generateTransactionNo()
    {
        return Carbon::createFromDate()->milli . carbon::now()->diffInMicroseconds();
    }

    /**
     * | Generate Random OTP 
     */
    public function generateOtp()
    {
        $otp = Carbon::createFromDate()->milli . random_int(100, 999);
        return $otp;
    }
}
