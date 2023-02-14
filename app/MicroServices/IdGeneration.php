<?php

namespace App\MicroServices;

use App\Models\Masters\IdGenerationParam;
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
     * | Generate Transaction ID
     */
    public function generateTransactionNo()
    {
        return Carbon::createFromDate()->milli . carbon::now()->diffInMicroseconds();
    }
}
