<?php

namespace App\Repository\Property\Concrete;

use App\Models\Property\PropActiveSafsDoc;
use App\Repository\Property\Interfaces\iDocumentOperationRepo;
use Exception;

class DocumentOperationRepo implements iDocumentOperationRepo
{
    public function getAllDocuments($request)
    {
        try {
            // return ("working");
            $documentDetails = PropActiveSafsDoc::join('ref_prop_docs_required', 'ref_prop_docs_required.id', '=', 'prop_active_safs_docs.doc_mstr_id')
                ->join('prop_active_safs', 'prop_active_safs.id', '=', 'prop_active_safs_docs.saf_id')
                ->select(
                    'prop_active_safs_docs.id',
                    'doc_path',
                    'prop_active_safs_docs.remarks',
                    'ref_prop_docs_required.doc_name'
                )
                ->where('prop_active_safs.workflow_id', $request->workflowId)
                ->where('prop_active_safs.id', $request->applicationId)
                ->get();

            if (empty($documentDetails['0'])) {
                return responseMsg(false, "Data Not Found!", "");
            }

            return responseMsg(true, "Document Details!", remove_null($documentDetails));
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }
}
