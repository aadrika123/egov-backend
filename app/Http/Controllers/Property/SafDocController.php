<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\MicroServices\DocUpload;
use App\Models\Masters\RefRequiredDocument;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Workflows\WfActiveDocument;
use App\Traits\Property\SafDoc;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config as FacadesConfig;

class SafDocController extends Controller
{
    /**
     * | Get Document Lists
     */
    public function getDocList(Request $req)
    {
        try {
            $mActiveSafs = new PropActiveSaf();
            $safsOwners = new PropActiveSafsOwner();
            $refSafs = $mActiveSafs->getSafNo($req->applicationId);                      // Get Saf Details
            if (!$refSafs)
                throw new Exception("Application Not Found for this id");
            $refSafOwners = $safsOwners->getOwnerDtlsBySafId($req->applicationId);
            $propTypeDocs = $this->getSafDocLists($refSafs);                              // Current Object(Saf Docuement List)
            $safOwnerDocs = $this->getOwnerDocLists($refSafOwners, $refSafs);             // (Owner Document List)
            $totalDocLists = collect($propTypeDocs)->merge($safOwnerDocs);
            return responseMsgs(true, "", remove_null($totalDocLists), "010203", "", "", 'POST', "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010203", "1.0", "", 'POST', "");
        }
    }

    /**
     * | Gettting Document List (1)
     * | Transer type initial mode 0 for other Case
     */
    public function getSafDocLists($refSafs)
    {
        $mRefReqDocs = new RefRequiredDocument();
        $propTypes = FacadesConfig::get('PropertyConstaint.PROPERTY-TYPE');
        $moduleId = FacadesConfig::get('module-constants.PROPERTY_MODULE_ID');
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
        return $filteredDocs;
    }

    /**
     * | Get Owner Document Lists
     */
    public function getOwnerDocLists($refOwners, $refSafs)
    {
        $documentList = array();
        $mRefReqDocs = new RefRequiredDocument();
        $moduleId = FacadesConfig::get('module-constants.PROPERTY_MODULE_ID');
        $isSpeciallyAbled = $refOwners->is_specially_abled;
        $isArmedForce = $refOwners->is_armed_force;

        if ($isSpeciallyAbled == true)
            $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "IS_SPECIALLY_ABLED")->requirements;
        if ($isArmedForce == true)
            $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "IS_ARMED_FORCE")->requirements;

        if (!empty($documentList))
            $filteredDocs = $this->filterDocument($documentList, $refSafs);                                     // function(1.2)
        else
            $filteredDocs = [];
        return $filteredDocs;
    }

    /**
     * | Vacant Land Required Doc lists (1.1)
     */
    public function vacantDocLists($mRefReqDocs, $moduleId, $transferType)
    {
        $confTransferTypes = FacadesConfig::get('PropertyConstaint.TRANSFER_MODES');
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
        $mWfActiveDocument = new WfActiveDocument();
        $safId = $refSafs->id;
        $workflowId = $refSafs->workflow_id;
        $uploadedDocs = $mWfActiveDocument->getDocByRefIds($safId, $workflowId, 1);
        $explodeDocs = collect(explode('#', $documentList));
        $filteredDocs = $explodeDocs->map(function ($explodeDoc) use ($uploadedDocs) {
            $document = explode(',', $explodeDoc);
            $key = array_shift($document);
            $reqDoc[$key] = collect($document)->map(function ($doc) use ($uploadedDocs) {
                $uploadedDoc = $uploadedDocs->where('doc_code', $doc)->first();
                $strLower = strtolower($doc);
                $strReplace = str_replace('_', ' ', $strLower);
                $arr = [
                    "documentCode" => $doc,
                    "docVal" => ucwords($strReplace),
                    "ownerId" => $uploadedDoc->owner_dtl_id ?? "",
                    "docPath" => $uploadedDoc->doc_path ?? ""
                ];
                return $arr;
            });
            return $reqDoc;
        });
        return $filteredDocs;
    }

    /**
     * | Created for Document Upload for SAFs
     */
    public function docUpload(Request $req)
    {
        $req->validate([
            "applicationId" => "required|numeric",
            "document" => "required|mimes:pdf,jpeg,png,jpg,gif",
            "docCode" => "required",
            "ownerId" => "nullable|numeric"
        ]);

        try {
            $metaReqs = array();
            $docUpload = new DocUpload;
            $mWfActiveDocument = new WfActiveDocument();
            $mActiveSafs = new PropActiveSaf();
            $relativePath = FacadesConfig::get('PropertyConstaint.SAF_RELATIVE_PATH');
            $getSafDtls = $mActiveSafs->getSafNo($req->applicationId);
            $refImageName = $req->docCode;
            $refImageName = $getSafDtls->id . '-' . $refImageName;
            $document = $req->document;
            $imageName = $docUpload->upload($refImageName, $document, $relativePath);

            $metaReqs['moduleId'] = FacadesConfig::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['activeId'] = $getSafDtls->id;
            $metaReqs['workflowId'] = $getSafDtls->workflow_id;
            $metaReqs['ulbId'] = $getSafDtls->ulb_id;
            $metaReqs['relativePath'] = $relativePath;
            $metaReqs['document'] = $imageName;
            $metaReqs['docCode'] = $req->docCode;
            $metaReqs['ownerDtlId'] = $req->ownerId;

            $metaReqs = new Request($metaReqs);
            $mWfActiveDocument->postDocuments($metaReqs);
            return responseMsgs(true, "Document Uploadation Successful", "", "010201", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | View Saf Uploaded Documents 
     */
    public function getUploadDocuments(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|numeric'
        ]);
        try {
            $mWfActiveDocument = new WfActiveDocument();
            $mActiveSafs = new PropActiveSaf();
            $moduleId = FacadesConfig::get('module-constants.PROPERTY_MODULE_ID');              // 1

            $safDetails = $mActiveSafs->getSafNo($req->applicationId);
            if (!$safDetails)
                throw new Exception("Application Not Found for this application Id");

            $workflowId = $safDetails->workflow_id;
            $documents = $mWfActiveDocument->getDocsByAppId($req->applicationId, $workflowId, $moduleId);
            return responseMsgs(true, "Uploaded Documents", remove_null($documents), "010102", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010202", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
}
