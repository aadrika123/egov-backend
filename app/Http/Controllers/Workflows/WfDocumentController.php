<?php

namespace App\Http\Controllers\Workflows;

use App\Http\Controllers\Controller;
use App\Models\Workflows\WfActiveDocument;
use Exception;
use Illuminate\Http\Request;

class WfDocumentController extends Controller
{
    /**
     * | Approve Or Reject Document 
     */
    public function docVerifyReject(Request $req)
    {
        $req->validate([
            'id' => 'required|numeric',
            'docRemarks' => 'required',
            'docStatus' => 'required'
        ]);

        try {
            $mWfDocument = new WfActiveDocument();
            $wfDocId = $req->id;
            $userId = authUser()->id;
            if ($req->docStatus == "Verified")
                $status = 1;
            if ($req->docStatus == "Rejected")
                $status = 2;
            $reqs = [
                'remarks' => $req->docRemarks,
                'verify_status' => $status,
                'action_taken_by' => $userId
            ];
            $mWfDocument->docVerifyReject($wfDocId, $reqs);
            return responseMsgs(true, $req->docRemarks . "Successfully", "", "1001", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "1001", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
}
