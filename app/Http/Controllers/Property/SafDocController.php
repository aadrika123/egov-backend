<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\MicroServices\DocUpload;
use App\Models\Property\PropActiveSaf;
use App\Models\Workflows\WfActiveDocument;
use Exception;
use Illuminate\Http\Request;

class SafDocController extends Controller
{
    /**
     * | Created for Document Upload for SAFs
     */
    public function docUpload(Request $req)
    {
        $req->validate([
            "safId" => "required|integer"
        ]);

        try {
            $metaReqs = array();
            $docUpload = new DocUpload;
            $mWfActiveDocument = new WfActiveDocument();
            $mActiveSafs = new PropActiveSaf();
            $getSafDtls = $mActiveSafs->getSafNo($req->safId);
            $refImageName = "Image";
            $relativePath = "Uploads/Property";
            $image = $req->image;
            $imageName = $docUpload->upload($refImageName, $image, $relativePath);
            $metaReqs['activeId'] = $getSafDtls->saf_no;
            $metaReqs['workflowId'] = $getSafDtls->workflow_id;
            $metaReqs['ulbId'] = $getSafDtls->ulb_id;
            $metaReqs['moduleId'] = 1;
            $metaReqs['relativePath'] = $relativePath;
            $metaReqs['image'] = $imageName;
            $metaReqs = new Request($metaReqs);
            $mWfActiveDocument->postDocuments($metaReqs);
            return responseMsgs(true, "Document Uploadation Successfull", "", "010201", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
}
