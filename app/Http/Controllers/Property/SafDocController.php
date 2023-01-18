<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\MicroServices\DocUpload;
use App\Models\Property\PropActiveSaf;
use App\Models\Workflows\WfActiveDocument;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config as FacadesConfig;
use PSpell\Config;

class SafDocController extends Controller
{
    /**
     * | Created for Document Upload for SAFs
     */
    public function docUpload(Request $req)
    {
        $req->validate([
            "applicationId" => "required|numeric",
            "document" => "required|mimes:pdf,jpeg,png,jpg,gif",
            "docMstrId" => "required|numeric",
            "ownerId" => "nullable|numeric",
            "docRefName" => "required"
        ]);

        try {
            $metaReqs = array();
            $docUpload = new DocUpload;
            $mWfActiveDocument = new WfActiveDocument();
            $mActiveSafs = new PropActiveSaf();
            $relativePath = FacadesConfig::get('PropertyConstaint.SAF_RELATIVE_PATH');
            $getSafDtls = $mActiveSafs->getSafNo($req->applicationId);
            $refImageName = $req->docRefName;
            $refImageName = $getSafDtls->id . '-' . str_replace(' ', '_', $refImageName);
            $document = $req->document;
            $imageName = $docUpload->upload($refImageName, $document, $relativePath);

            $metaReqs['moduleId'] = FacadesConfig::get('module-constants.PROPERTY_MODULE_ID');
            $metaReqs['activeId'] = $getSafDtls->saf_no;
            $metaReqs['workflowId'] = $getSafDtls->workflow_id;
            $metaReqs['ulbId'] = $getSafDtls->ulb_id;
            $metaReqs['relativePath'] = $relativePath;
            $metaReqs['image'] = $imageName;
            $metaReqs['docMstrId'] = $req->docMstrId;
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

            $safDetails = $mActiveSafs->getSafNo($req->applicationId);
            if (!$safDetails)
                throw new Exception("Application Not Found for this application Id");

            $safNo = $safDetails->saf_no;
            $documents = $mWfActiveDocument->getDocsByAppNo($safNo);
            return responseMsgs(true, "Uploaded Documents", $documents, "010102", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010202", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
}
