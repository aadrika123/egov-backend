<?php

namespace App\Traits\Property;

use App\Models\Masters\RefRequiredDocument;
use Exception;
use Illuminate\Support\Facades\Config;

/**
 * | Trait Used for Gettting the Document Lists By Property Types and Owner Details
 */
trait SafDoc
{
    /**
     * | Gettting Document List (1)
     * | Transer type initial mode 0 for other Case
     */
    public function getSafDocLists($refSafs)
    {
        $mRefReqDocs = new RefRequiredDocument();
        $propTypes = Config::get('PropertyConstaint.PROPERTY-TYPE');
        $moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');
        $propType = $refSafs->prop_type_mstr_id;
        $transferType = $refSafs->transfer_mode_mstr_id;

        $flip = flipConstants($propTypes);
        switch ($propType) {
            case $flip['FLATS / UNIT IN MULTI STORIED BUILDING'];
                $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "PROP_FLATS")->requirements;
                break;
            case $flip['INDEPENDENT BUILDING'];
                $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "PROP_INDEPENDENT_BUILDING")->requirements;
                break;
            case $flip['SUPER STRUCTURE'];
                $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "PROP_SUPER_STRUCTURE")->requirements;
                break;
            case $flip['VACANT LAND'];
                $documentList = $this->vacantDocLists($mRefReqDocs, $moduleId, $transferType);     // Function (1.1)
                break;
        }

        $filteredDocs = $this->filterDocument($documentList, $refSafs);                                     // function(1.2)
        return responseMsg(true, "", $filteredDocs);
    }

    /**
     * | Vacant Land Required Doc lists (1.1)
     */
    public function vacantDocLists($mRefReqDocs, $moduleId, $transferType)
    {
        $confTransferTypes = Config::get('PropertyConstaint.TRANSFER_MODES');
        $transerTypes = flipConstants($confTransferTypes);
        switch ($transferType) {
            case  $transerTypes['Sale'];
                $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "PROP_VACANT_SALE")->requirements;
                break;
            case  $transerTypes['Gift'];
                $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "PROP_VACANT_GIFT")->requirements;
                break;
            case  $transerTypes['Will'];
                $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "PROP_VACANT_WILL")->requirements;
                break;
            case  $transerTypes['Lease'];
                $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "PROP_VACANT_LEASE")->requirements;
                break;
            case  $transerTypes['Partition'];
                $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "PROP_VACANT_PARTITION")->requirements;
                break;
            case  $transerTypes['Succession'];
                $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "PROP_VACANT_SUCCESSION")->requirements;
                break;
            default:
                throw new Exception("Not Available Documents List for this Transfer Type");
        }

        return $documentList;
    }

    /**
     * | Filter Document(1.2)
     */
    public function filterDocument($documentList, $refSafs)
    {
        $workflowId = $refSafs->workflow_id;
        $ulbId = $refSafs->ulb_id;
        $explodeDocs = collect(explode('#', $documentList));
        $filteredDocs = $explodeDocs->map(function ($explodeDoc) {
            $document = explode(',', $explodeDoc);
            $key = array_shift($document);
            $reqDoc[$key] = collect($document)->map(function ($doc) {
                $strLower = strtolower($doc);
                $strReplace = str_replace('_', ' ', $strLower);
                $arr = [
                    "documentCode" => $doc,
                    "docVal" => ucwords($strReplace),
                    "docPath" => ""
                ];
                return $arr;
            });
            return $reqDoc;
        });
        return $filteredDocs;
    }
}
