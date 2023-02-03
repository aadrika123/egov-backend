<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\MicroServices\DocUpload;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsOwner;
use App\Models\Workflows\WfActiveDocument;
use App\Traits\Property\SafDoc;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config as FacadesConfig;

/**
 * | Created On=01-02-2023 
 * | Created By=Anshu Kumar
 * | Created for=Document Upload 
 */
class SafDocController extends Controller
{
    use SafDoc;
    /**
     * | Get Document Lists
     */
    public function getDocList(Request $req)
    {
        $req->validate([
            'applicationId' => 'required|numeric'
        ]);

        try {
            $mActiveSafs = new PropActiveSaf();
            $safsOwners = new PropActiveSafsOwner();
            $refSafs = $mActiveSafs->getSafNo($req->applicationId);                      // Get Saf Details
            if (!$refSafs)
                throw new Exception("Application Not Found for this id");
            $refSafOwners = $safsOwners->getOwnersBySafId($req->applicationId);
            $propTypeDocs['listDocs'] = $this->getSafDocLists($refSafs);                // Current Object(Saf Docuement List)

            $safOwnerDocs['ownerDocs'] = collect($refSafOwners)->map(function ($owner) use ($refSafs) {
                return $this->getOwnerDocLists($owner, $refSafs);
            });

            $totalDocLists = collect($propTypeDocs)->merge($safOwnerDocs);
            $totalDocLists['docUploadStatus'] = $refSafs->doc_upload_status;
            $totalDocLists['docVerifyStatus'] = $refSafs->doc_verify_status;

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
        $documentList = $this->getPropTypeDocList($refSafs);
        $filteredDocs = $this->filterDocument($documentList, $refSafs);                            // function(1.2)
        return $filteredDocs;
    }

    /**
     * | Get Owner Document Lists
     */
    public function getOwnerDocLists($refOwners, $refSafs)
    {
        $mWfActiveDocument = new WfActiveDocument();
        $moduleId = FacadesConfig::get('module-constants.PROPERTY_MODULE_ID');
        $documentList = $this->getOwnerDocs($refOwners);

        if (!empty($documentList)) {
            $ownerPhoto = $mWfActiveDocument->getOwnerPhotograph($refSafs['id'], $refSafs->workflow_id, $moduleId, $refOwners['id']);
            $filteredDocs['ownerDetails'] = [
                'ownerId' => $refOwners['id'],
                'name' => $refOwners['owner_name'],
                'mobile' => $refOwners['mobile_no'],
                'guardian' => $refOwners['guardian_name'],
                'uploadedDoc' => $ownerPhoto->doc_path ?? "",
                'verifyStatus' => $ownerPhoto->verify_status ?? ""
            ];
            $filteredDocs['documents'] = $this->filterDocument($documentList, $refSafs, $refOwners['id']);                                     // function(1.2)
        } else
            $filteredDocs = [];
        return $filteredDocs;
    }

    /**
     * | Filter Document(1.2)
     */
    public function filterDocument($documentList, $refSafs, $ownerId = null)
    {
        $mWfActiveDocument = new WfActiveDocument();
        $safId = $refSafs->id;
        $workflowId = $refSafs->workflow_id;
        $moduleId = FacadesConfig::get('module-constants.PROPERTY_MODULE_ID');
        $uploadedDocs = $mWfActiveDocument->getDocByRefIds($safId, $workflowId, $moduleId);
        $explodeDocs = collect(explode('#', $documentList));

        $filteredDocs = $explodeDocs->map(function ($explodeDoc) use ($uploadedDocs, $ownerId) {
            $document = explode(',', $explodeDoc);
            $key = array_shift($document);

            $documents = collect();

            collect($document)->map(function ($item) use ($uploadedDocs, $documents, $ownerId) {
                $uploadedDoc = $uploadedDocs->where('doc_code', $item)
                    ->where('owner_dtl_id', $ownerId)
                    ->first();
                if ($uploadedDoc) {
                    $response = [
                        "uploadedDocId" => $uploadedDoc->id ?? "",
                        "documentCode" => $item,
                        "ownerId" => $uploadedDoc->owner_dtl_id ?? "",
                        "docPath" => $uploadedDoc->doc_path ?? "",
                        "verifyStatus" => $uploadedDoc->verify_status ?? "",
                        "remarks" => $uploadedDoc->remarks ?? "",
                    ];
                    $documents->push($response);
                }
            });
            $reqDoc['docType'] = $key;
            $reqDoc['uploadedDoc'] = $documents->first();

            $reqDoc['masters'] = collect($document)->map(function ($doc) use ($uploadedDocs) {
                $uploadedDoc = $uploadedDocs->where('doc_code', $doc)->first();
                $strLower = strtolower($doc);
                $strReplace = str_replace('_', ' ', $strLower);
                $arr = [
                    "documentCode" => $doc,
                    "docVal" => ucwords($strReplace),
                    "uploadedDoc" => $uploadedDoc->doc_path ?? "",
                    "uploadedDocId" => $uploadedDoc->id ?? "",
                    "verifyStatus'" => $uploadedDoc->verify_status ?? "",
                    "remarks" => $uploadedDoc->remarks ?? "",
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
        // return $this->checkFullDocUpload($req->applicationId);
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
            return responseMsgs(true, ["docVerifyStatus" => $safDetails->doc_verify_status], remove_null($documents), "010102", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010202", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Check Full Upload Doc Status
     */
    public function checkFullDocUpload($applicationId)
    {
        $docList = array();
        $uploadDocList = array();
        $mActiveSafs = new PropActiveSaf();
        $mSafsOwners = new PropActiveSafsOwner();
        $mWfActiveDocument = new WfActiveDocument();
        $refSafs = $mActiveSafs->getSafNo($applicationId);                      // Get Saf Details
        $refSafOwners = $mSafsOwners->getOwnersBySafId($applicationId);
        $propListDocs = $this->getPropTypeDocList($refSafs);
        $docList['propDocs'] = explode('#', $propListDocs);
        $ownerDocList = collect($refSafOwners)->map(function ($owner) use ($refSafs) {
            return [
                'ownerId' => $owner->id,
                'docs'  => explode('#', $this->getOwnerDocs($owner))
            ];
        });
        $docList['ownerDocs'] = $ownerDocList;
        $refDocList = $mWfActiveDocument->getDocsByActiveId($applicationId);
        $uploadDocList['ownerDocs'] = $refDocList->where('owner_dtl_id', '!=', null)->values()->groupBy('owner_dtl_id');
        $uploadDocList['propDocs'] = $refDocList->where('owner_dtl_id', null)->values();

        $collectUploadDocList = collect();
        collect($uploadDocList['propDocs'])->map(function ($item) use ($collectUploadDocList) {
            return $collectUploadDocList->push($item['doc_code']);
        });

        $collectUploadDocList;
        $mPropDocs = collect($docList['propDocs']);

        $flag = 0;
        // collect($mPropDocs)->map(function ($doc) use ($collectUploadDocList, $flag) {
        //     $explodeDoc = explode(',', $doc);
        //     array_shift($explodeDoc);
        //     $flag = 1;
        //     return $explodeDoc;
        // });
        foreach ($mPropDocs as $item) {
            $explodeDoc = explode(',', $item);
            array_shift($explodeDoc);
            $flag = 1;
            return $explodeDoc;
        }
        return $flag;
    }
}
