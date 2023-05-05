<?php

namespace App\Traits\Property;

use App\Models\Masters\RefRequiredDocument;
use App\Models\Workflows\WfActiveDocument;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;

/**
 * | Trait Used for Gettting the Document Lists By Property Types and Owner Details
 * | Created On-02-02-2022 
 * | Created By-Anshu Kumar
 */
trait SafDoc
{
    public function getPropTypeDocList($refSafs)
    {
        $mRefReqDocs = new RefRequiredDocument();
        $propTypes = Config::get('PropertyConstaint.PROPERTY-TYPE');
        $moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');
        $propType = $refSafs->prop_type_mstr_id;

        $flip = flipConstants($propTypes);
        switch ($propType) {
            case $flip['FLATS / UNIT IN MULTI STORIED BUILDING']:
                $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "PROP_FLATS")->requirements;
                break;
            case $flip['INDEPENDENT BUILDING']:
                $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "PROP_INDEPENDENT_BUILDING")->requirements;
                break;
            case $flip['SUPER STRUCTURE']:
                $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "PROP_SUPER_STRUCTURE")->requirements;
                break;
            case $flip['VACANT LAND']:
                $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "PROP_VACANT_LAND")->requirements;     // Function (1.1)
                break;
            case $flip['OCCUPIED PROPERTY']:
                $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "PROP_OCCUPIED_PROPERTY")->requirements;     // Function (1.1)
                break;
        }
        if ($refSafs->is_trust == true)
            $documentList .= $mRefReqDocs->getDocsByDocCode($moduleId, "PROP_TRUST")->requirements;

        return $documentList;
    }


    /**
     * | Get Owner Document Lists
     */
    public function getOwnerDocs($refOwners)
    {
        $mRefReqDocs = new RefRequiredDocument();
        $moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');
        $isSpeciallyAbled = $refOwners->is_specially_abled;
        $isArmedForce = $refOwners->is_armed_force;

        if ($isSpeciallyAbled == true)
            $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "OWNER_IS_SPECIALLY_ABLED")->requirements;

        if ($isArmedForce == true)
            $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "OWNER_IS_ARMED_FORCE")->requirements;

        if ($isSpeciallyAbled == true && $isArmedForce == true)
            $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "OWNER_SPECIALLY_ARMED")->requirements;

        if ($isSpeciallyAbled == false && $isArmedForce == false)                                           // Condition for the Extra Documents
            $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "OWNER_EXTRA_DOCUMENT")->requirements;

        return $documentList;
    }
}
