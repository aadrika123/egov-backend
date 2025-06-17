<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefRequiredDocument extends Model
{
    use HasFactory;

    /**
     * | Get All Documents by Document Code
       | Common Function
     */
    public function getDocsByDocCode($moduldId, $docCode)
    {
        return RefRequiredDocument::select('requirements')
            ->where('module_id', $moduldId)
            ->where('code', $docCode)
            ->first();
    }

    /**
     * | Get Documents where module Id
       | Reference Function : __construct()
     */
    public function getDocsByModuleId($moduleId)
    {
        return RefRequiredDocument::select('code', 'requirements')
            ->where('module_id', $moduleId)
            ->get();
    }

    /**
     * | Get  All Document Collictively For Array Of DocCode
       | Common Function
     */
    public function getCollectiveDocByCode($moduldId, $docCodes)
    {
        return RefRequiredDocument::select(
            'requirements',
            'code'
        )
            ->where('module_id', $moduldId)
            ->whereIn('code', $docCodes)
            ->get();
    }
}
